<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta box isolado para configurações do Hero da vitrine.
 */
class Vitrine_Hero_Meta {

    const META_KEY = '_vitrine_page_settings';

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_date_meta_box' ), 15 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20 );
        add_action( 'save_post_vitrine', array( $this, 'save' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    public static function default_page_settings() {
        return array(
            'show_header'          => '1',
            'show_footer'          => '1',
            'page_bg_color'        => '',
            'hero_image'           => '',
            'hero_bg_color'        => '#333333',
            'hero_text'            => '',
            'hero_text_color'      => '#ffffff',
            'hero_overlay_opacity' => '50',
            'hero_text_shadow'     => '1',
            'hero_height'          => '400',
            'hero_font_size'       => '36',
            'hero_text_align'      => 'center',
            'hero_description'     => '',
            'hero_desc_size'       => '18',
            'hero_desc_color'      => '',
            'hero_desc_align'      => 'center',
            'hero_desc_max_width'  => '',
            'hero_text_bold'       => '1',
            'hero_text_italic'     => '0',
            'hero_desc_bold'       => '0',
            'hero_desc_italic'     => '0',
            'hero_date'            => '',
            'hero_date_size'       => '16',
            'hero_date_color'      => '',
            'hero_date_align'      => 'right',
            'hero_date_format'     => 'auto',
            'hero_date_prefix'     => '',
            'hero_date_show_prefix' => '0',
            'custom_css'           => '',
        );
    }

    public static function get_settings( $post_id ) {
        $settings = get_post_meta( $post_id, self::META_KEY, true );
        if ( ! is_array( $settings ) ) {
            return self::default_page_settings();
        }
        $merged = wp_parse_args( $settings, self::default_page_settings() );
        if ( ! array_key_exists( 'hero_desc_align', $settings ) || '' === (string) $settings['hero_desc_align'] ) {
            $merged['hero_desc_align'] = $merged['hero_text_align'];
        }
        return $merged;
    }

    /**
     * Mescla configurações parciais preservando chaves não enviadas (ex.: hero vs builder).
     */
    public static function merge_settings( $post_id, array $partial ) {
        $existing = self::get_settings( $post_id );
        return array_merge( $existing, $partial );
    }

    public static function hero_keys() {
        return array(
            'hero_image',
            'hero_bg_color',
            'hero_text',
            'hero_text_color',
            'hero_overlay_opacity',
            'hero_text_shadow',
            'hero_height',
            'hero_font_size',
            'hero_text_align',
            'hero_description',
            'hero_desc_size',
            'hero_desc_color',
            'hero_desc_align',
            'hero_desc_max_width',
            'hero_text_bold',
            'hero_text_italic',
            'hero_desc_bold',
            'hero_desc_italic',
            'hero_date',
            'hero_date_size',
            'hero_date_color',
            'hero_date_align',
            'hero_date_format',
            'hero_date_prefix',
            'hero_date_show_prefix',
        );
    }

    public static function builder_keys() {
        return array(
            'show_header',
            'show_footer',
            'page_bg_color',
            'custom_css',
        );
    }

    public static function sanitize_hero( array $input ) {
        $align = isset( $input['hero_text_align'] ) ? $input['hero_text_align'] : 'center';
        if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
            $align = 'center';
        }

        $desc_align = isset( $input['hero_desc_align'] ) ? $input['hero_desc_align'] : $align;
        if ( ! in_array( $desc_align, array( 'left', 'center', 'right' ), true ) ) {
            $desc_align = $align;
        }

        $date_align = isset( $input['hero_date_align'] ) ? sanitize_key( $input['hero_date_align'] ) : 'right';
        if ( ! in_array( $date_align, array( 'left', 'center', 'right' ), true ) ) {
            $date_align = 'right';
        }

        $date_format = isset( $input['hero_date_format'] ) ? sanitize_key( $input['hero_date_format'] ) : 'auto';
        if ( ! in_array( $date_format, array( 'auto', 'br', 'en' ), true ) ) {
            $date_format = 'auto';
        }

        $hero_date = isset( $input['hero_date'] ) ? sanitize_text_field( $input['hero_date'] ) : '';
        if ( $hero_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $hero_date ) ) {
            $hero_date = '';
        }

        $hero_date_color = isset( $input['hero_date_color'] ) ? sanitize_hex_color( $input['hero_date_color'] ) : '';
        $hero_date_prefix = isset( $input['hero_date_prefix'] ) ? sanitize_text_field( $input['hero_date_prefix'] ) : '';
        if ( function_exists( 'mb_substr' ) ) {
            $hero_date_prefix = mb_substr( $hero_date_prefix, 0, 80 );
        } else {
            $hero_date_prefix = substr( $hero_date_prefix, 0, 80 );
        }

        return array(
            'hero_image'           => isset( $input['hero_image'] ) ? esc_url_raw( $input['hero_image'] ) : '',
            'hero_bg_color'        => isset( $input['hero_bg_color'] ) ? ( sanitize_hex_color( $input['hero_bg_color'] ) ?: '#333333' ) : '#333333',
            'hero_text'            => isset( $input['hero_text'] ) ? sanitize_text_field( $input['hero_text'] ) : '',
            'hero_text_color'      => isset( $input['hero_text_color'] ) ? sanitize_hex_color( $input['hero_text_color'] ) : '#ffffff',
            'hero_overlay_opacity' => isset( $input['hero_overlay_opacity'] ) ? absint( $input['hero_overlay_opacity'] ) : 50,
            'hero_text_shadow'     => ! empty( $input['hero_text_shadow'] ) ? '1' : '0',
            'hero_height'          => isset( $input['hero_height'] ) ? absint( $input['hero_height'] ) : 400,
            'hero_font_size'       => isset( $input['hero_font_size'] ) ? absint( $input['hero_font_size'] ) : 36,
            'hero_text_align'      => $align,
            'hero_description'     => isset( $input['hero_description'] ) ? wp_kses_post( $input['hero_description'] ) : '',
            'hero_desc_size'       => isset( $input['hero_desc_size'] ) ? absint( $input['hero_desc_size'] ) : 18,
            'hero_desc_color'      => isset( $input['hero_desc_color'] ) ? sanitize_hex_color( $input['hero_desc_color'] ) : '',
            'hero_desc_align'      => $desc_align,
            'hero_desc_max_width'  => isset( $input['hero_desc_max_width'] ) ? absint( $input['hero_desc_max_width'] ) : 0,
            'hero_text_bold'       => ! empty( $input['hero_text_bold'] ) ? '1' : '0',
            'hero_text_italic'     => ! empty( $input['hero_text_italic'] ) ? '1' : '0',
            'hero_desc_bold'       => ! empty( $input['hero_desc_bold'] ) ? '1' : '0',
            'hero_desc_italic'     => ! empty( $input['hero_desc_italic'] ) ? '1' : '0',
            'hero_date'             => $hero_date,
            'hero_date_size'        => isset( $input['hero_date_size'] ) ? max( 10, min( 72, absint( $input['hero_date_size'] ) ) ) : 16,
            'hero_date_color'       => $hero_date_color ? $hero_date_color : '',
            'hero_date_align'       => $date_align,
            'hero_date_format'      => $date_format,
            'hero_date_prefix'      => $hero_date_prefix,
            'hero_date_show_prefix' => ! empty( $input['hero_date_show_prefix'] ) ? '1' : '0',
        );
    }

    /**
     * Formato PHP da data do hero (br = d/m/Y, en = m/d/Y, auto = idioma).
     */
    public static function get_date_php_format( $format = 'auto' ) {
        $format = sanitize_key( (string) $format );
        if ( 'br' === $format ) {
            return 'd/m/Y';
        }
        if ( 'en' === $format ) {
            return 'm/d/Y';
        }

        $lang = class_exists( 'Vitrine_I18n' ) ? Vitrine_I18n::get_display_language() : get_locale();
        $family = strtolower( preg_replace( '/[^a-z].*$/', '', strtolower( str_replace( '-', '_', (string) $lang ) ) ) );
        if ( 'en' === $family ) {
            return 'm/d/Y';
        }

        return 'd/m/Y';
    }

    /**
     * Prefixo exibido antes da data (customizado ou padrão do idioma).
     */
    public static function get_hero_date_prefix( $settings = array() ) {
        $custom = isset( $settings['hero_date_prefix'] ) ? trim( (string) $settings['hero_date_prefix'] ) : '';
        if ( '' !== $custom ) {
            return $custom;
        }
        if ( empty( $settings['hero_date_show_prefix'] ) || '1' !== (string) $settings['hero_date_show_prefix'] ) {
            return '';
        }
        $lang = class_exists( 'Vitrine_I18n' ) ? Vitrine_I18n::get_display_language() : null;
        return class_exists( 'Vitrine_I18n' )
            ? Vitrine_I18n::translate( 'Updated on:', 'ui.vitrine_date_updated', $lang )
            : 'Updated on:';
    }

    /**
     * Partes da data do hero para o front (iso, número, prefixo, rótulo completo).
     */
    public static function get_hero_date_display( $settings = array() ) {
        $date_str = isset( $settings['hero_date'] ) ? (string) $settings['hero_date'] : '';
        $format   = isset( $settings['hero_date_format'] ) ? $settings['hero_date_format'] : 'auto';
        $formatted = self::format_hero_date( $date_str, $format );
        $prefix    = $formatted ? self::get_hero_date_prefix( $settings ) : '';
        $label     = $formatted;
        if ( $prefix && $formatted ) {
            $label = rtrim( $prefix ) . ' ' . $formatted;
        }

        return array(
            'iso'       => $date_str,
            'formatted' => $formatted,
            'prefix'    => $prefix,
            'label'     => $label,
        );
    }

    /**
     * Formata a data do hero para exibição no front.
     *
     * @param string $date_str YYYY-MM-DD
     * @param string $format   auto|br|en
     */
    public static function format_hero_date( $date_str, $format = 'auto' ) {
        if ( empty( $date_str ) ) {
            return '';
        }

        $timestamp = strtotime( $date_str . ' 12:00:00' );
        if ( ! $timestamp ) {
            return sanitize_text_field( $date_str );
        }

        return date_i18n( self::get_date_php_format( $format ), $timestamp );
    }

    /**
     * Metabox da data da vitrine (logo abaixo das opções de header/footer).
     */
    public function add_date_meta_box() {
        add_meta_box(
            'vitrine_date',
            Vitrine_I18n::t( 'Vitrine date', 'ui.vitrine_date' ),
            array( $this, 'render_date_meta_box' ),
            'vitrine',
            'normal',
            'high'
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'vitrine_hero',
            Vitrine_I18n::t( 'Vitrine Hero', 'ui.hero_title' ),
            array( $this, 'render_meta_box' ),
            'vitrine',
            'normal',
            'low'
        );
    }

    /**
     * Imprime o nonce de save uma única vez (data ou hero).
     */
    private function maybe_print_save_nonce() {
        static $printed = false;
        if ( $printed ) {
            return;
        }
        wp_nonce_field( 'vitrine_hero_save', 'vitrine_hero_nonce' );
        $printed = true;
    }

    public function render_date_meta_box( $post ) {
        $this->maybe_print_save_nonce();

        $s          = self::get_settings( $post->ID );
        $date_color = ! empty( $s['hero_date_color'] ) ? $s['hero_date_color'] : ( ! empty( $s['hero_text_color'] ) ? $s['hero_text_color'] : '#ffffff' );
        $date_fmt   = ! empty( $s['hero_date_format'] ) ? $s['hero_date_format'] : 'auto';
        $prefix_ph  = Vitrine_I18n::t( 'Updated on:', 'ui.vitrine_date_updated' );
        ?>
        <div id="vitrine-date-meta-box" class="vitrine-date-metabox">
            <p class="description vitrine-date-metabox__hint"><?php echo esc_html( Vitrine_I18n::t( 'Shown at the bottom of the hero. Format follows the vitrine language unless overridden.', 'ui.vitrine_date_hint' ) ); ?></p>
            <div class="vitrine-date-metabox__fields">
                <div class="vitrine-date-metabox__field vitrine-date-metabox__field--date">
                    <label for="vitrine-hero-date"><?php echo esc_html( Vitrine_I18n::t( 'Vitrine date', 'ui.vitrine_date' ) ); ?></label>
                    <input type="date" name="vitrine_hero[hero_date]" id="vitrine-hero-date" value="<?php echo esc_attr( $s['hero_date'] ); ?>" />
                </div>
                <div class="vitrine-date-metabox__field">
                    <label for="vitrine-hero-date-format"><?php echo esc_html( Vitrine_I18n::t( 'Date format', 'ui.vitrine_date_format' ) ); ?></label>
                    <select name="vitrine_hero[hero_date_format]" id="vitrine-hero-date-format">
                        <option value="auto"<?php selected( $date_fmt, 'auto' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'Auto (language)', 'ui.vitrine_date_format_auto' ) ); ?></option>
                        <option value="br"<?php selected( $date_fmt, 'br' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'Brazil (13/10/2026)', 'ui.vitrine_date_format_br' ) ); ?></option>
                        <option value="en"<?php selected( $date_fmt, 'en' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'English (10/13/2026)', 'ui.vitrine_date_format_en' ) ); ?></option>
                    </select>
                </div>
                <div class="vitrine-date-metabox__field">
                    <label for="vitrine-hero-date-size"><?php echo esc_html( Vitrine_I18n::t( 'Size (px)', 'ui.vitrine_date_size' ) ); ?></label>
                    <input type="number" name="vitrine_hero[hero_date_size]" id="vitrine-hero-date-size" value="<?php echo esc_attr( $s['hero_date_size'] ); ?>" min="10" max="72" step="1" class="small-text" />
                </div>
                <div class="vitrine-date-metabox__field">
                    <label for="vitrine-hero-date-color"><?php echo esc_html( Vitrine_I18n::t( 'Color', 'ui.vitrine_date_color' ) ); ?></label>
                    <input type="color" name="vitrine_hero[hero_date_color]" id="vitrine-hero-date-color" value="<?php echo esc_attr( $date_color ); ?>" />
                </div>
                <div class="vitrine-date-metabox__field">
                    <label for="vitrine-hero-date-align"><?php echo esc_html( Vitrine_I18n::t( 'Alignment', 'ui.vitrine_date_align' ) ); ?></label>
                    <select name="vitrine_hero[hero_date_align]" id="vitrine-hero-date-align">
                        <option value="left"<?php selected( $s['hero_date_align'], 'left' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'Left', 'ui.align_left' ) ); ?></option>
                        <option value="center"<?php selected( $s['hero_date_align'], 'center' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'Center', 'ui.align_center' ) ); ?></option>
                        <option value="right"<?php selected( $s['hero_date_align'], 'right' ); ?>><?php echo esc_html( Vitrine_I18n::t( 'Right', 'ui.align_right' ) ); ?></option>
                    </select>
                </div>
            </div>
            <div class="vitrine-date-metabox__prefix">
                <label class="vitrine-date-metabox__check" for="vitrine-hero-date-show-prefix">
                    <input type="checkbox" name="vitrine_hero[hero_date_show_prefix]" id="vitrine-hero-date-show-prefix" value="1"<?php checked( $s['hero_date_show_prefix'], '1' ); ?> />
                    <?php echo esc_html( Vitrine_I18n::t( 'Show language prefix (“Updated on:” / “Atualizado em:”)', 'ui.vitrine_date_show_prefix' ) ); ?>
                </label>
                <div class="vitrine-date-metabox__field vitrine-date-metabox__field--prefix">
                    <label for="vitrine-hero-date-prefix"><?php echo esc_html( Vitrine_I18n::t( 'Prefix (optional)', 'ui.vitrine_date_prefix' ) ); ?></label>
                    <input type="text" name="vitrine_hero[hero_date_prefix]" id="vitrine-hero-date-prefix" value="<?php echo esc_attr( $s['hero_date_prefix'] ); ?>" maxlength="80" class="widefat" placeholder="<?php echo esc_attr( $prefix_ph ); ?>" />
                    <p class="description"><?php echo esc_html( Vitrine_I18n::t( 'Text shown before the date, e.g. “Updated on: 13/10/2026”. Leave empty to show the date only, or to use the language default when enabled.', 'ui.vitrine_date_prefix_hint' ) ); ?></p>
                </div>
            </div>
            <p class="description vitrine-date-metabox__live" id="vitrine-date-live-preview"></p>
        </div>
        <?php
    }

    public function enqueue( $hook ) {
        global $post_type;
        if ( 'vitrine' !== $post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'vitrine-hero-admin-css',
            VITRINE_URL . 'assets/css/editor.css',
            array(),
            VITRINE_VERSION . '.' . filemtime( VITRINE_PATH . 'assets/css/editor.css' )
        );
        wp_enqueue_script(
            'vitrine-hero-admin-js',
            VITRINE_URL . 'assets/js/hero-admin.js',
            array( 'jquery' ),
            VITRINE_VERSION . '.' . filemtime( VITRINE_PATH . 'assets/js/hero-admin.js' ),
            true
        );

        $post_id  = get_the_ID();
        $settings = self::get_settings( $post_id );
        wp_localize_script( 'vitrine-hero-admin-js', 'vitrineHeroData', array(
            'settings' => $settings,
            'dateI18n' => array(
                'lang'          => Vitrine_I18n::get_display_language(),
                'defaultPrefix' => Vitrine_I18n::t( 'Updated on:', 'ui.vitrine_date_updated' ),
            ),
        ) );
    }

    public function render_meta_box( $post ) {
        $this->maybe_print_save_nonce();

        $s = self::get_settings( $post->ID );
        ?>
        <div id="vitrine-hero-meta-box" class="vitrine-hero-metabox">
            <p class="vitrine-hero-metabox__intro description">Banner no topo da vitrine publicada. Salve ou atualize a vitrine para aplicar.</p>

            <div class="vitrine-hero-metabox__layout">
                <div class="vitrine-hero-metabox__form">

                    <section class="vitrine-hero-section">
                        <h4 class="vitrine-hero-section__title">Fundo</h4>
                        <div class="vitrine-hero-section__body">
                            <div class="vitrine-hero-field vitrine-hero-field--full">
                                <label for="vitrine-hero-image">Imagem de fundo</label>
                                <div class="vitrine-image-field vitrine-hero-image-field">
                                    <?php if ( ! empty( $s['hero_image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $s['hero_image'] ); ?>" class="vitrine-image-preview" alt="" />
                                    <?php endif; ?>
                                    <input type="hidden" name="vitrine_hero[hero_image]" id="vitrine-hero-image" value="<?php echo esc_attr( $s['hero_image'] ); ?>" />
                                    <div class="vitrine-hero-image-field__actions">
                                        <button type="button" class="button" id="vitrine-hero-select-image">Selecionar</button>
                                        <?php if ( ! empty( $s['hero_image'] ) ) : ?>
                                            <button type="button" class="button" id="vitrine-hero-remove-image">Remover</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="vitrine-hero-section__grid vitrine-hero-section__grid--3">
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-height">Altura (px)</label>
                                    <input type="number" name="vitrine_hero[hero_height]" id="vitrine-hero-height" value="<?php echo esc_attr( $s['hero_height'] ); ?>" min="100" max="1000" step="10" class="small-text" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-opacity">Opacidade do fade <span id="vitrine-hero-opacity-val"><?php echo esc_html( $s['hero_overlay_opacity'] ); ?>%</span></label>
                                    <input type="range" name="vitrine_hero[hero_overlay_opacity]" id="vitrine-hero-opacity" min="0" max="100" value="<?php echo esc_attr( $s['hero_overlay_opacity'] ); ?>" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-bg-color">Cor de fundo (sem imagem)</label>
                                    <input type="color" name="vitrine_hero[hero_bg_color]" id="vitrine-hero-bg-color" value="<?php echo esc_attr( $s['hero_bg_color'] ); ?>" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="vitrine-hero-section">
                        <h4 class="vitrine-hero-section__title">Título</h4>
                        <div class="vitrine-hero-section__body">
                            <div class="vitrine-hero-field vitrine-hero-field--full">
                                <label for="vitrine-hero-text">Frase de destaque</label>
                                <input type="text" name="vitrine_hero[hero_text]" id="vitrine-hero-text" value="<?php echo esc_attr( $s['hero_text'] ); ?>" placeholder="Título do hero" class="widefat" />
                                <div class="vitrine-hero-format">
                                    <button type="button" class="vitrine-format-btn<?php echo '1' === $s['hero_text_bold'] ? ' is-active' : ''; ?>" data-target="text" data-prop="bold" title="Negrito"><b>B</b></button>
                                    <button type="button" class="vitrine-format-btn<?php echo '1' === $s['hero_text_italic'] ? ' is-active' : ''; ?>" data-target="text" data-prop="italic" title="Itálico"><i>I</i></button>
                                    <input type="hidden" name="vitrine_hero[hero_text_bold]" id="vitrine-hero-text-bold" value="<?php echo esc_attr( $s['hero_text_bold'] ); ?>" />
                                    <input type="hidden" name="vitrine_hero[hero_text_italic]" id="vitrine-hero-text-italic" value="<?php echo esc_attr( $s['hero_text_italic'] ); ?>" />
                                </div>
                            </div>
                            <div class="vitrine-hero-section__grid vitrine-hero-section__grid--4">
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-font-size">Tamanho (px)</label>
                                    <input type="number" name="vitrine_hero[hero_font_size]" id="vitrine-hero-font-size" value="<?php echo esc_attr( $s['hero_font_size'] ); ?>" min="12" max="120" step="2" class="small-text" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-text-color">Cor</label>
                                    <input type="color" name="vitrine_hero[hero_text_color]" id="vitrine-hero-text-color" value="<?php echo esc_attr( $s['hero_text_color'] ?: '#ffffff' ); ?>" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-text-align">Alinhamento</label>
                                    <select name="vitrine_hero[hero_text_align]" id="vitrine-hero-text-align">
                                        <option value="left"<?php selected( $s['hero_text_align'], 'left' ); ?>>Esquerda</option>
                                        <option value="center"<?php selected( $s['hero_text_align'], 'center' ); ?>>Centro</option>
                                        <option value="right"<?php selected( $s['hero_text_align'], 'right' ); ?>>Direita</option>
                                    </select>
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-text-shadow">Sombra dos textos</label>
                                    <select name="vitrine_hero[hero_text_shadow]" id="vitrine-hero-text-shadow">
                                        <option value="1"<?php selected( $s['hero_text_shadow'], '1' ); ?>>Ativada</option>
                                        <option value="0"<?php selected( $s['hero_text_shadow'], '0' ); ?>>Desativada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="vitrine-hero-section">
                        <h4 class="vitrine-hero-section__title">Descrição</h4>
                        <div class="vitrine-hero-section__body">
                            <div class="vitrine-hero-field vitrine-hero-field--full">
                                <label for="vitrine-hero-description">Texto</label>
                                <div class="vitrine-wysiwyg-toolbar" id="vitrine-hero-desc-toolbar">
                                    <button type="button" class="vitrine-wysiwyg-btn" data-cmd="bold" title="Negrito"><b>B</b></button>
                                    <button type="button" class="vitrine-wysiwyg-btn" data-cmd="italic" title="Itálico"><i>I</i></button>
                                    <button type="button" class="vitrine-wysiwyg-btn" data-cmd="underline" title="Sublinhado"><u>U</u></button>
                                    <button type="button" class="vitrine-wysiwyg-btn" data-cmd="removeFormat" title="Limpar formatação">&#10005;</button>
                                </div>
                                <div id="vitrine-hero-description" class="vitrine-aranha-wysiwyg" contenteditable="true"><?php echo wp_kses_post( $s['hero_description'] ); ?></div>
                                <textarea name="vitrine_hero[hero_description]" id="vitrine-hero-description-input" class="hidden" style="display:none;"><?php echo esc_textarea( $s['hero_description'] ); ?></textarea>
                            </div>
                            <div class="vitrine-hero-section__grid vitrine-hero-section__grid--4">
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-desc-size">Tamanho (px)</label>
                                    <input type="number" name="vitrine_hero[hero_desc_size]" id="vitrine-hero-desc-size" value="<?php echo esc_attr( $s['hero_desc_size'] ); ?>" min="10" max="60" step="1" class="small-text" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-desc-color">Cor</label>
                                    <input type="color" name="vitrine_hero[hero_desc_color]" id="vitrine-hero-desc-color" value="<?php echo esc_attr( ! empty( $s['hero_desc_color'] ) ? $s['hero_desc_color'] : ( $s['hero_text_color'] ?: '#ffffff' ) ); ?>" />
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-desc-align">Alinhamento</label>
                                    <select name="vitrine_hero[hero_desc_align]" id="vitrine-hero-desc-align">
                                        <option value="left"<?php selected( $s['hero_desc_align'], 'left' ); ?>>Esquerda</option>
                                        <option value="center"<?php selected( $s['hero_desc_align'], 'center' ); ?>>Centro</option>
                                        <option value="right"<?php selected( $s['hero_desc_align'], 'right' ); ?>>Direita</option>
                                    </select>
                                </div>
                                <div class="vitrine-hero-field">
                                    <label for="vitrine-hero-desc-max-width">Largura máx. (px)</label>
                                    <input type="number" name="vitrine_hero[hero_desc_max_width]" id="vitrine-hero-desc-max-width" value="<?php echo esc_attr( $s['hero_desc_max_width'] ); ?>" min="0" max="1400" step="10" class="small-text" placeholder="auto" />
                                    <p class="description">0 = sem limite.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <aside class="vitrine-hero-metabox__aside">
                    <h4 class="vitrine-hero-section__title">Pré-visualização</h4>
                    <div id="vitrine-hero-preview" class="vitrine-hero-preview"></div>
                    <p class="description vitrine-hero-preview__empty">Preencha imagem, título, descrição ou data para ver a prévia.</p>
                </aside>
            </div>
        </div>
        <?php
    }

    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['vitrine_hero_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vitrine_hero_nonce'] ) ), 'vitrine_hero_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( 'vitrine' !== $post->post_type ) {
            return;
        }

        $raw  = isset( $_POST['vitrine_hero'] ) && is_array( $_POST['vitrine_hero'] ) ? wp_unslash( $_POST['vitrine_hero'] ) : array();
        $hero = self::sanitize_hero( $raw );
        $merged = self::merge_settings( $post_id, $hero );

        update_post_meta( $post_id, self::META_KEY, $merged );
    }
}
