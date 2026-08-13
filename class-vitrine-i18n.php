<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Traduções do builder: WordPress i18n, overrides por idioma e Polylang.
 */
class Vitrine_I18n {

    const TEXT_DOMAIN = 'builder-vitrine';
    const OPTION_OVERRIDES = 'vitrine_builder_translations';

    /** @var bool */
    private static $polylang_strings_registered = false;

    public static function init() {
        add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );
        add_action( 'init', array( __CLASS__, 'register_polylang_strings' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'maybe_seed_bundled_translations' ), 4 );
        add_action( 'admin_init', array( __CLASS__, 'maybe_migrate_stale_labels' ), 5 );
    }

    /**
     * Importa o CSV empacotado uma vez, preenchendo apenas chaves vazias/faltantes.
     */
    public static function maybe_seed_bundled_translations() {
        $flag = 'vitrine_seeded_bundled_translations_v3';
        if ( get_option( $flag ) ) {
            return;
        }

        $path = self::get_bundled_csv_path();
        if ( is_readable( $path ) ) {
            $parsed = self::parse_translations_csv( $path );
            if ( ! is_wp_error( $parsed ) && is_array( $parsed ) ) {
                self::import_parsed_translations( $parsed, false, true );
            }
        }

        update_option( $flag, '1', false );
    }

    /**
     * Corrige overrides antigos que deixavam dois elementos com nomes iguais.
     * imagelinks: "Imagem + Texto" → "Imagem + Links" (textimage continua "Texto + Imagem").
     */
    public static function maybe_migrate_stale_labels() {
        $flag = 'vitrine_migrated_imagelinks_label_v2';
        if ( get_option( $flag ) ) {
            return;
        }

        $overrides = self::get_overrides();
        $changed   = false;
        $stale     = array(
            'Imagem + Texto',
            'Image + Text',
            'Imagen + Texto',
            'Imagem + texto',
            'Image + text',
        );
        $replacements = array(
            'pt' => 'Imagem + Links',
            'en' => 'Image + Links',
            'es' => 'Imagen + Enlaces',
        );

        foreach ( $overrides as $lang => $rows ) {
            if ( ! is_array( $rows ) || empty( $rows['element.imagelinks.label'] ) ) {
                continue;
            }
            $current = trim( (string) $rows['element.imagelinks.label'] );
            foreach ( $stale as $old ) {
                if ( 0 === strcasecmp( $current, $old ) ) {
                    $slug = strtolower( (string) $lang );
                    $overrides[ $lang ]['element.imagelinks.label'] = isset( $replacements[ $slug ] )
                        ? $replacements[ $slug ]
                        : 'Imagem + Links';
                    $changed = true;
                    break;
                }
            }
        }

        if ( $changed ) {
            self::save_overrides( $overrides );
        }

        update_option( $flag, '1', false );
    }

    public static function load_textdomain() {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname( plugin_basename( VITRINE_PATH . 'builder-vitrine.php' ) ) . '/languages'
        );
    }

    /**
     * Idioma ativo no admin do builder (idioma do post > Polylang > usuário > site).
     */
    public static function get_admin_language() {
        $available = self::get_available_languages();

        // 1) Idioma do post em edição (mais confiável no admin com Polylang).
        $post_id = self::get_editing_post_id();
        if ( $post_id && function_exists( 'pll_get_post_language' ) ) {
            $post_lang = pll_get_post_language( $post_id, 'slug' );
            if ( $post_lang ) {
                return self::normalize_language_slug( $post_lang, $available );
            }
        }

        // 1b) Novo post: Polylang passa new_lang / lang na URL.
        if ( isset( $_GET['new_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $new_lang = sanitize_key( wp_unslash( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $new_lang ) {
                return self::normalize_language_slug( $new_lang, $available );
            }
        }
        if ( isset( $_GET['lang'] ) && function_exists( 'pll_languages_list' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $url_lang = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $url_lang && isset( $available[ $url_lang ] ) ) {
                return $url_lang;
            }
        }

        // 2) Idioma atual do Polylang (quando disponível).
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            if ( $lang ) {
                return self::normalize_language_slug( $lang, $available );
            }
        }

        // 3) Locale do usuário mapeado para o slug do site (pt_BR → pt).
        $locale = get_user_locale();
        if ( function_exists( 'pll_languages_list' ) ) {
            $map   = pll_languages_list( array( 'fields' => 'locale' ) );
            $slugs = pll_languages_list( array( 'fields' => 'slug' ) );
            if ( is_array( $map ) && is_array( $slugs ) ) {
                $idx = array_search( $locale, $map, true );
                if ( false !== $idx && isset( $slugs[ $idx ] ) ) {
                    return self::normalize_language_slug( $slugs[ $idx ], $available );
                }
            }
        }

        return self::normalize_language_slug( $locale, $available );
    }

    /**
     * ID do post em edição no admin (quando houver).
     */
    private static function get_editing_post_id() {
        global $post;
        if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $id ) {
                return $id;
            }
        }
        if ( isset( $post->ID ) ) {
            return absint( $post->ID );
        }
        return 0;
    }

    /**
     * Normaliza slug/locale para um idioma disponível no site.
     *
     * @param string $lang
     * @param array  $available
     * @return string
     */
    public static function normalize_language_slug( $lang, array $available = array() ) {
        $lang = (string) $lang;
        if ( '' === $lang ) {
            return $lang;
        }
        if ( ! $available ) {
            $available = self::get_available_languages();
        }
        if ( isset( $available[ $lang ] ) ) {
            return $lang;
        }

        $family = strtolower( preg_replace( '/[^a-z].*$/', '', strtolower( str_replace( '-', '_', $lang ) ) ) );
        if ( '' === $family ) {
            $family = strtolower( substr( $lang, 0, 2 ) );
        }

        $resolved = self::resolve_site_lang_slug( $family, $available );
        return $resolved ? $resolved : $lang;
    }

    /**
     * Lista idiomas para o painel (slug => nome).
     */
    public static function get_available_languages() {
        if ( function_exists( 'pll_languages_list' ) ) {
            $slugs = pll_languages_list( array( 'fields' => 'slug' ) );
            $names = pll_languages_list( array( 'fields' => 'name' ) );
            $out   = array();
            if ( is_array( $slugs ) ) {
                foreach ( $slugs as $i => $slug ) {
                    $out[ $slug ] = isset( $names[ $i ] ) ? $names[ $i ] : $slug;
                }
            }
            if ( $out ) {
                return $out;
            }
        }
        return array( get_locale() => 'Default language' );
    }

    /**
     * Candidatos de chave de idioma para lookup de overrides (pt_BR, pt-br, pt…).
     *
     * @param string $lang
     * @return string[]
     */
    private static function language_lookup_candidates( $lang ) {
        $lang = (string) $lang;
        $list = array( $lang );

        $norm_us = strtolower( str_replace( '-', '_', $lang ) );
        $norm_hy = strtolower( str_replace( '_', '-', $lang ) );
        $list[]  = $norm_us;
        $list[]  = $norm_hy;

        if ( preg_match( '/^([a-z]{2})/', $norm_us, $m ) ) {
            $list[] = $m[1];
            $resolved = self::resolve_site_lang_slug( $m[1], self::get_available_languages() );
            if ( $resolved ) {
                $list[] = $resolved;
            }
        }

        return array_values( array_unique( array_filter( $list ) ) );
    }

    /**
     * Busca tradução nos overrides tentando aliases de idioma.
     *
     * @param string $lang
     * @param string $name
     * @return string|null
     */
    private static function find_override_translation( $lang, $name ) {
        $overrides = self::get_overrides();
        if ( ! $overrides ) {
            return null;
        }

        foreach ( self::language_lookup_candidates( $lang ) as $candidate ) {
            if ( isset( $overrides[ $candidate ][ $name ] ) && '' !== $overrides[ $candidate ][ $name ] ) {
                return $overrides[ $candidate ][ $name ];
            }
        }

        // Último recurso: se só houver um bloco de overrides da mesma família.
        if ( preg_match( '/^([a-z]{2})/', strtolower( str_replace( '-', '_', (string) $lang ) ), $m ) ) {
            $family = $m[1];
            foreach ( $overrides as $slug => $rows ) {
                if ( ! is_array( $rows ) ) {
                    continue;
                }
                $slug_l = strtolower( str_replace( array( '-', '_' ), '', (string) $slug ) );
                if ( 0 === strpos( $slug_l, $family ) && isset( $rows[ $name ] ) && '' !== $rows[ $name ] ) {
                    return $rows[ $name ];
                }
            }
        }

        return null;
    }

    /**
     * Traduz string do builder.
     *
     * @param string      $default Texto padrão (msgid).
     * @param string      $name    Chave estável (ex: ui.canvas_placeholder).
     * @param string|null $lang    Slug Polylang ou locale; null = admin atual.
     */
    public static function translate( $default, $name, $lang = null ) {
        $default = (string) $default;
        $name    = (string) $name;
        if ( null === $lang ) {
            $lang = self::get_admin_language();
        }

        $from_override = self::find_override_translation( $lang, $name );
        if ( null !== $from_override ) {
            return $from_override;
        }

        if ( function_exists( 'pll_translate_string' ) ) {
            foreach ( self::language_lookup_candidates( $lang ) as $pll_lang ) {
                $pll = pll_translate_string( $default, $pll_lang );
                if ( is_string( $pll ) && $pll !== $default ) {
                    return $pll;
                }
            }
        }

        return __( $default, self::TEXT_DOMAIN );
    }

    /** Atalho para UI do builder. */
    public static function t( $default, $name = '' ) {
        if ( '' === $name ) {
            $name = 'ui.' . sanitize_key( $default );
        }
        return self::translate( $default, $name );
    }

    public static function element_label( $slug, $default_label ) {
        $key   = 'element.' . sanitize_key( $slug ) . '.label';
        $label = self::translate( $default_label, $key );

        // Segurança: nunca exibir o nome antigo do imagelinks (colidia com Texto + Imagem).
        if ( 'imagelinks' === $slug ) {
            $stale = array( 'imagem + texto', 'image + text', 'imagen + texto' );
            if ( in_array( strtolower( trim( $label ) ), $stale, true ) ) {
                return $default_label ? $default_label : 'Imagem + Links';
            }
        }

        return $label;
    }

    public static function field_label( $slug, $field_name, $default_label ) {
        return self::translate(
            $default_label,
            'element.' . sanitize_key( $slug ) . '.field.' . sanitize_key( $field_name )
        );
    }

    public static function field_option_label( $slug, $field_name, $option_value, $default_label ) {
        return self::translate(
            $default_label,
            'element.' . sanitize_key( $slug ) . '.field.' . sanitize_key( $field_name ) . '.option.' . sanitize_key( $option_value )
        );
    }

    public static function get_overrides() {
        $stored = get_option( self::OPTION_OVERRIDES, array() );
        return is_array( $stored ) ? $stored : array();
    }

    public static function save_overrides( array $overrides ) {
        update_option( self::OPTION_OVERRIDES, $overrides, false );
    }

    /**
     * Catálogo completo de strings traduzíveis (chave => default).
     */
    public static function get_string_catalog() {
        $catalog = self::get_ui_string_catalog();

        $elements = Vitrine_Plugin::load_elements();
        foreach ( $elements as $slug => $el ) {
            $catalog[ 'element.' . $slug . '.label' ] = $el->label();
            foreach ( $el->fields() as $field ) {
                if ( empty( $field['name'] ) ) {
                    continue;
                }
                $fname = $field['name'];
                $catalog[ 'element.' . $slug . '.field.' . $fname ] = isset( $field['label'] ) ? $field['label'] : $fname;
                if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
                    foreach ( $field['options'] as $opt_val => $opt_label ) {
                        $catalog[ 'element.' . $slug . '.field.' . $fname . '.option.' . $opt_val ] = $opt_label;
                    }
                }
            }
        }

        return apply_filters( 'vitrine_builder_string_catalog', $catalog );
    }

    /**
     * Strings da interface do editor (sidebar, canvas, abas).
     */
    public static function get_ui_string_catalog() {
        return array(
            'ui.elements'                      => 'Elements',
            'ui.structural'                    => 'Structural',
            'ui.search_elements'               => 'Search element...',
            'ui.no_elements_found'             => 'No elements found.',
            'ui.settings'                      => 'Settings',
            'ui.settings_empty'                => 'Click an element on the canvas to edit its settings.',
            'ui.content_tab'                   => 'Content',
            'ui.style_tab'                     => 'Styles',
            'ui.canvas_placeholder'            => 'Drag elements here',
            'ui.template_picker_title'         => 'Initial template',
            'ui.template_picker_hint'          => 'Or drag containers from the sidebar to start from scratch',
            'ui.template_complete_name'        => 'Complete showcase',
            'ui.template_complete_desc'        => 'Highlights + Text + Toggle + Text + Banner',
            'ui.collapse_elements_panel'       => 'Collapse elements panel',
            'ui.show_elements_panel'           => 'Show elements panel',
            'ui.collapse_settings_panel'       => 'Collapse settings panel',
            'ui.show_settings_panel'           => 'Show settings panel',
            'ui.close_settings_panel'          => 'Close panel',
            'ui.builder_title'                 => 'Vitrine Builder',
            'ui.hero_title'                    => 'Vitrine Hero',
            'ui.page_custom_css'               => 'Custom vitrine CSS',
            'ui.clone_to_language'             => 'Clone to language',
            'ui.clone_to_language_help'        => 'Creates or updates the Polylang translation with the same layout and content.',
            'ui.clone_success'                 => 'Vitrine cloned. Opening translation…',
            'ui.clone_error'                   => 'Could not clone vitrine.',
            'ui.translations_page_title'       => 'Builder translations',
            'ui.translations_menu'             => 'Translations',
            'ui.container_default'             => 'Container',
            'ui.vitrine_date'                  => 'Vitrine date',
            'ui.vitrine_date_hint'             => 'Shown at the bottom of the hero.',
            'ui.vitrine_date_size'             => 'Size (px)',
            'ui.vitrine_date_color'            => 'Color',
            'ui.vitrine_date_align'            => 'Alignment',
            'ui.align_left'                    => 'Left',
            'ui.align_center'                  => 'Center',
            'ui.align_right'                   => 'Right',
            'ui.translations_page_intro'       => 'Override element names and builder interface strings per language. Works with Polylang and standard WordPress translations (.po/.mo).',
            'ui.translations_save'             => 'Save translations',
            'ui.translations_saved'            => 'Translations saved.',
            'ui.translations_language'         => 'Language',
            'ui.translations_key'              => 'Key',
            'ui.translations_default'          => 'Default text',
            'ui.translations_value'            => 'Translation',
            'ui.translations_filter'           => 'Filter strings…',
            'ui.polylang_strings_hint'         => 'Strings are also registered in Polylang → String translations (group: Builder Vitrine).',
            'ui.translations_import_title'     => 'Import CSV',
            'ui.translations_import_intro'     => 'Upload a translations CSV (Key, Default text, Translation English, Translation Español, Translation Português) or load the file bundled with the plugin.',
            'ui.translations_import_upload'    => 'Upload CSV',
            'ui.translations_import_bundled'   => 'Load plugin CSV',
            'ui.translations_import_success'   => 'Translations imported from CSV.',
            'ui.translations_import_error'     => 'Could not import the CSV file.',
            'ui.translations_export'           => 'Export CSV',
            'ui.translations_import_mode'      => 'Import mode',
            'ui.translations_import_merge'     => 'Merge (keep existing, fill/update from CSV)',
            'ui.translations_import_replace'   => 'Replace languages found in the CSV',
        );
    }

    /**
     * Strings passadas ao editor.js (valores já traduzidos para o idioma admin).
     */
    public static function get_editor_js_strings() {
        $catalog = self::get_string_catalog();
        $out     = array();
        foreach ( $catalog as $key => $default ) {
            if ( 0 === strpos( $key, 'ui.' ) ) {
                $out[ $key ] = self::translate( $default, $key );
            }
        }
        $out['container_default'] = self::t( 'Container', 'ui.container_default' );
        return $out;
    }

    public static function localize_elements_for_editor( array $elements_raw ) {
        $elements_js = array();
        foreach ( $elements_raw as $slug => $el ) {
            $fields_data = array();
            foreach ( $el->fields() as $field ) {
                $f = $field;
                if ( isset( $f['label'] ) ) {
                    $f['label'] = self::field_label( $slug, $f['name'], $f['label'] );
                }
                if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
                    $opts = array();
                    foreach ( $field['options'] as $opt_key => $opt_label ) {
                        $opts[ $opt_key ] = self::field_option_label( $slug, $field['name'], $opt_key, $opt_label );
                    }
                    $f['options'] = $opts;
                }
                $fields_data[] = $f;
            }
            $elements_js[ $slug ] = array(
                'slug'     => $slug,
                'label'    => self::element_label( $slug, $el->label() ),
                'icon'     => $el->icon(),
                'defaults' => $el->defaults(),
                'fields'   => $fields_data,
            );
        }
        return $elements_js;
    }

    public static function register_polylang_strings() {
        if ( ! function_exists( 'pll_register_string' ) || self::$polylang_strings_registered ) {
            return;
        }
        self::$polylang_strings_registered = true;

        Vitrine_Plugin::load_elements();
        $catalog = self::get_string_catalog();
        foreach ( $catalog as $name => $string ) {
            pll_register_string( $name, $string, 'Builder Vitrine', false );
        }
    }

    /**
     * Caminho do CSV de traduções empacotado no plugin.
     */
    public static function get_bundled_csv_path() {
        return VITRINE_PATH . 'assets/traducao/traducao.csv';
    }

    /**
     * Lê e interpreta um CSV de traduções.
     *
     * Formato: Key,Default text,Translation English,Translation Español,Translation Português
     *
     * @param string $file_path Caminho absoluto do arquivo.
     * @return array|\WP_Error [ lang_slug => [ key => translation ] ]
     */
    public static function parse_translations_csv( $file_path ) {
        if ( ! is_string( $file_path ) || ! $file_path || ! is_readable( $file_path ) ) {
            return new WP_Error( 'vitrine_csv_unreadable', 'CSV file not readable.' );
        }

        $handle = fopen( $file_path, 'rb' );
        if ( ! $handle ) {
            return new WP_Error( 'vitrine_csv_open', 'Could not open CSV file.' );
        }

        $bom = fread( $handle, 3 );
        if ( "\xEF\xBB\xBF" !== $bom ) {
            rewind( $handle );
        }

        $header = fgetcsv( $handle );
        if ( ! is_array( $header ) || count( $header ) < 2 ) {
            fclose( $handle );
            return new WP_Error( 'vitrine_csv_header', 'Invalid CSV header.' );
        }

        $header  = array_map( array( __CLASS__, 'normalize_csv_header' ), $header );
        $key_idx = array_search( 'key', $header, true );
        if ( false === $key_idx ) {
            $key_idx = 0;
        }

        $available = self::get_available_languages();
        $col_langs = array();
        foreach ( $header as $col => $label ) {
            if ( (int) $col === (int) $key_idx ) {
                continue;
            }
            if ( in_array( $label, array( 'default text', 'default', 'texto padrao', 'texto padrão' ), true ) ) {
                continue;
            }
            $lang = self::match_language_column( $label, $available );
            if ( $lang ) {
                $col_langs[ $col ] = $lang;
            }
        }

        if ( ! $col_langs ) {
            fclose( $handle );
            return new WP_Error( 'vitrine_csv_langs', 'No matching language columns found for this site.' );
        }

        $parsed = array();
        foreach ( $col_langs as $lang ) {
            $parsed[ $lang ] = array();
        }

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( ! is_array( $row ) || ! isset( $row[ $key_idx ] ) ) {
                continue;
            }
            $key = trim( (string) $row[ $key_idx ] );
            if ( '' === $key || 0 === strpos( $key, '#' ) ) {
                continue;
            }
            foreach ( $col_langs as $col => $lang ) {
                if ( ! isset( $row[ $col ] ) ) {
                    continue;
                }
                $val = trim( (string) $row[ $col ] );
                if ( '' === $val ) {
                    continue;
                }
                $parsed[ $lang ][ $key ] = $val;
            }
        }
        fclose( $handle );

        return $parsed;
    }

    /**
     * Aplica traduções do CSV aos overrides salvos.
     *
     * @param array $parsed     Resultado de parse_translations_csv().
     * @param bool  $replace    Se true, substitui o idioma; se false, merge.
     * @param bool  $only_empty Se true, não sobrescreve chaves já preenchidas.
     * @return array{langs:string[],count:int}
     */
    public static function import_parsed_translations( array $parsed, $replace = false, $only_empty = false ) {
        $overrides = self::get_overrides();
        $count     = 0;
        $langs     = array();

        foreach ( $parsed as $lang => $pairs ) {
            if ( ! is_array( $pairs ) || ! $pairs ) {
                continue;
            }
            $lang = sanitize_key( $lang );
            if ( ! $lang ) {
                continue;
            }
            $langs[] = $lang;
            if ( $replace || ! isset( $overrides[ $lang ] ) || ! is_array( $overrides[ $lang ] ) ) {
                $overrides[ $lang ] = array();
            }
            foreach ( $pairs as $key => $value ) {
                $key   = sanitize_text_field( (string) $key );
                $value = sanitize_text_field( (string) $value );
                if ( '' === $key || '' === $value ) {
                    continue;
                }
                if ( $only_empty && isset( $overrides[ $lang ][ $key ] ) && '' !== $overrides[ $lang ][ $key ] ) {
                    continue;
                }
                $overrides[ $lang ][ $key ] = $value;
                $count++;
            }
        }

        self::save_overrides( $overrides );

        return array(
            'langs' => array_values( array_unique( $langs ) ),
            'count' => $count,
        );
    }

    /**
     * Gera CSV a partir dos overrides + catálogo.
     *
     * @return string
     */
    public static function build_translations_csv_export() {
        Vitrine_Plugin::load_elements();
        $catalog   = self::get_string_catalog();
        $overrides = self::get_overrides();
        $available = self::get_available_languages();

        $lang_cols = array(
            'en' => 'Translation English',
            'es' => 'Translation Español',
            'pt' => 'Translation Português',
        );

        foreach ( array_keys( $available ) as $slug ) {
            $base = strtolower( str_replace( array( '-', '_' ), '', $slug ) );
            if ( 0 === strpos( $base, 'en' ) || 0 === strpos( $base, 'es' ) || 0 === strpos( $base, 'pt' ) ) {
                continue;
            }
            if ( ! isset( $lang_cols[ $slug ] ) ) {
                $lang_cols[ $slug ] = 'Translation ' . $slug;
            }
        }

        $slug_for_col = array();
        foreach ( $lang_cols as $logical => $label ) {
            $slug_for_col[ $logical ] = self::resolve_site_lang_slug( $logical, $available );
        }

        $out = fopen( 'php://temp', 'r+' );
        fputcsv( $out, array_merge( array( 'Key', 'Default text' ), array_values( $lang_cols ) ) );

        foreach ( $catalog as $key => $default ) {
            $row = array( $key, $default );
            foreach ( $lang_cols as $logical => $label ) {
                $slug = $slug_for_col[ $logical ];
                $val  = '';
                if ( $slug && isset( $overrides[ $slug ][ $key ] ) ) {
                    $val = $overrides[ $slug ][ $key ];
                } elseif ( isset( $overrides[ $logical ][ $key ] ) ) {
                    $val = $overrides[ $logical ][ $key ];
                }
                $row[] = $val;
            }
            fputcsv( $out, $row );
        }

        rewind( $out );
        $csv = stream_get_contents( $out );
        fclose( $out );

        return is_string( $csv ) ? $csv : '';
    }

    /**
     * @param string $header
     * @return string
     */
    private static function normalize_csv_header( $header ) {
        $header = (string) $header;
        $header = preg_replace( '/^\xEF\xBB\xBF/', '', $header );
        if ( function_exists( 'remove_accents' ) ) {
            $header = remove_accents( $header );
        }
        $header = strtolower( trim( $header ) );
        $header = preg_replace( '/\s+/', ' ', $header );
        return $header;
    }

    /**
     * @param string $label
     * @param array  $available
     * @return string|null
     */
    private static function match_language_column( $label, array $available ) {
        $label = self::normalize_csv_header( $label );

        $families = array(
            'en' => array( 'english', 'ingles', 'translation english' ),
            'es' => array( 'espanol', 'spanish', 'translation espanol', 'castellano' ),
            'pt' => array( 'portugues', 'portuguese', 'translation portugues', 'brasil', 'brazil' ),
        );

        $family = null;
        foreach ( $families as $code => $needles ) {
            foreach ( $needles as $needle ) {
                if ( false !== strpos( $label, $needle ) ) {
                    $family = $code;
                    break 2;
                }
            }
        }

        if ( ! $family && preg_match( '/\b([a-z]{2})(?:[_\-\s][a-z]{2})?\b/', $label, $m ) ) {
            $candidate = $m[1];
            if ( in_array( $candidate, array( 'en', 'es', 'pt', 'fr', 'de', 'it' ), true ) ) {
                $family = $candidate;
            }
        }

        if ( ! $family ) {
            return null;
        }

        return self::resolve_site_lang_slug( $family, $available );
    }

    /**
     * @param string $family
     * @param array  $available
     * @return string|null
     */
    private static function resolve_site_lang_slug( $family, array $available ) {
        $family = strtolower( preg_replace( '/[^a-z]/', '', (string) $family ) );
        if ( '' === $family ) {
            return null;
        }

        if ( isset( $available[ $family ] ) ) {
            return $family;
        }

        $prefs = array(
            'en' => array( 'en', 'en_us', 'en-us', 'en_gb', 'en-gb' ),
            'es' => array( 'es', 'es_es', 'es-es', 'es_mx', 'es-mx' ),
            'pt' => array( 'pt', 'pt_br', 'pt-br', 'pt_pt', 'pt-pt' ),
        );
        $candidates = isset( $prefs[ $family ] ) ? $prefs[ $family ] : array( $family );

        foreach ( $candidates as $cand ) {
            foreach ( $available as $slug => $name ) {
                $slug_l = strtolower( $slug );
                $cand_l = strtolower( $cand );
                if ( $slug_l === $cand_l || str_replace( '_', '-', $slug_l ) === str_replace( '_', '-', $cand_l ) ) {
                    return $slug;
                }
            }
        }

        foreach ( $available as $slug => $name ) {
            $norm = strtolower( str_replace( array( '-', '_' ), '', $slug ) );
            if ( 0 === strpos( $norm, $family ) ) {
                return $slug;
            }
        }

        if ( 1 === count( $available ) ) {
            $keys = array_keys( $available );
            $only = (string) $keys[0];
            $norm = strtolower( str_replace( array( '-', '_' ), '', $only ) );
            if ( 0 === strpos( $norm, $family ) ) {
                return $only;
            }
            $name = (string) reset( $available );
            if ( 'pt' === $family && ( false !== strpos( $norm, 'pt' ) || false !== stripos( $name, 'portug' ) ) ) {
                return $only;
            }
        }

        return $family;
    }
}
