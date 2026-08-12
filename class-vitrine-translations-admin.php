<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Painel admin: traduzir nomes de elementos e strings do builder por idioma.
 */
class Vitrine_Translations_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_import' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_export' ) );
    }

    public static function register_menu() {
        add_submenu_page(
            'edit.php?post_type=vitrine',
            Vitrine_I18n::t( 'Builder translations', 'ui.translations_page_title' ),
            Vitrine_I18n::t( 'Translations', 'ui.translations_menu' ),
            'manage_options',
            'vitrine-builder-translations',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function handle_save() {
        if ( empty( $_POST['vitrine_translations_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vitrine_translations_nonce'] ) ), 'vitrine_save_translations' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $lang = isset( $_POST['vitrine_trans_lang'] ) ? sanitize_key( wp_unslash( $_POST['vitrine_trans_lang'] ) ) : '';
        if ( ! $lang ) {
            return;
        }

        $raw_values = isset( $_POST['vitrine_trans_value'] ) && is_array( $_POST['vitrine_trans_value'] )
            ? wp_unslash( $_POST['vitrine_trans_value'] )
            : array();

        $overrides = Vitrine_I18n::get_overrides();
        if ( ! isset( $overrides[ $lang ] ) || ! is_array( $overrides[ $lang ] ) ) {
            $overrides[ $lang ] = array();
        }

        $catalog = Vitrine_I18n::get_string_catalog();
        foreach ( $catalog as $key => $default ) {
            if ( ! isset( $raw_values[ $key ] ) ) {
                continue;
            }
            $val = sanitize_text_field( $raw_values[ $key ] );
            if ( '' === $val || $val === $default ) {
                unset( $overrides[ $lang ][ $key ] );
            } else {
                $overrides[ $lang ][ $key ] = $val;
            }
        }

        Vitrine_I18n::save_overrides( $overrides );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'    => 'vitrine-builder-translations',
                    'lang'    => $lang,
                    'updated' => '1',
                ),
                admin_url( 'edit.php?post_type=vitrine' )
            )
        );
        exit;
    }

    public static function handle_import() {
        if ( empty( $_POST['vitrine_translations_import_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vitrine_translations_import_nonce'] ) ), 'vitrine_import_translations' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $replace = ! empty( $_POST['vitrine_trans_import_replace'] );
        $source  = isset( $_POST['vitrine_trans_import_source'] ) ? sanitize_key( wp_unslash( $_POST['vitrine_trans_import_source'] ) ) : 'upload';

        $file_path = '';

        if ( 'bundled' === $source ) {
            $file_path = Vitrine_I18n::get_bundled_csv_path();
        } else {
            if ( empty( $_FILES['vitrine_trans_csv']['tmp_name'] ) ) {
                self::redirect_import_result( 'error' );
            }
            $tmp = $_FILES['vitrine_trans_csv'];
            if ( ! empty( $tmp['error'] ) || empty( $tmp['tmp_name'] ) || ! is_uploaded_file( $tmp['tmp_name'] ) ) {
                self::redirect_import_result( 'error' );
            }
            $name = isset( $tmp['name'] ) ? (string) $tmp['name'] : '';
            $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
            if ( 'csv' !== $ext ) {
                self::redirect_import_result( 'error' );
            }
            $file_path = $tmp['tmp_name'];
        }

        $parsed = Vitrine_I18n::parse_translations_csv( $file_path );
        if ( is_wp_error( $parsed ) ) {
            self::redirect_import_result( 'error' );
        }

        $result = Vitrine_I18n::import_parsed_translations( $parsed, $replace );
        $lang   = ! empty( $result['langs'][0] ) ? $result['langs'][0] : '';

        self::redirect_import_result(
            '1',
            $lang,
            array(
                'count' => isset( $result['count'] ) ? (int) $result['count'] : 0,
                'langs' => implode( ',', $result['langs'] ),
            )
        );
    }

    public static function handle_export() {
        if ( empty( $_GET['vitrine_export_translations'] ) ) {
            return;
        }
        if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'vitrine_export_translations' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $csv = Vitrine_I18n::build_translations_csv_export();
        $filename = 'vitrine-traducoes-' . gmdate( 'Y-m-d' ) . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo "\xEF\xBB\xBF";
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download.
        exit;
    }

    /**
     * @param string $status  '1' | 'error'
     * @param string $lang
     * @param array  $extra
     */
    private static function redirect_import_result( $status, $lang = '', $extra = array() ) {
        $args = array(
            'page'     => 'vitrine-builder-translations',
            'imported' => $status,
        );
        if ( $lang ) {
            $args['lang'] = $lang;
        }
        foreach ( $extra as $k => $v ) {
            $args[ $k ] = $v;
        }
        wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=vitrine' ) ) );
        exit;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        Vitrine_Plugin::load_elements();
        $languages = Vitrine_I18n::get_available_languages();
        $lang_keys = array_keys( $languages );
        $current_lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
        if ( ! $current_lang || ! isset( $languages[ $current_lang ] ) ) {
            $current_lang = $lang_keys ? $lang_keys[0] : get_locale();
        }

        $catalog   = Vitrine_I18n::get_string_catalog();
        $overrides = Vitrine_I18n::get_overrides();
        $saved     = isset( $_GET['updated'] ) && '1' === $_GET['updated'];
        $imported  = isset( $_GET['imported'] ) ? sanitize_text_field( wp_unslash( $_GET['imported'] ) ) : '';
        $import_ok    = ( '1' === $imported );
        $import_error = ( 'error' === $imported );
        $import_count = isset( $_GET['count'] ) ? intval( wp_unslash( $_GET['count'] ) ) : 0;
        $import_langs = isset( $_GET['langs'] ) ? sanitize_text_field( wp_unslash( $_GET['langs'] ) ) : '';

        $bundled_exists = file_exists( Vitrine_I18n::get_bundled_csv_path() );
        $export_url     = wp_nonce_url(
            add_query_arg(
                array(
                    'post_type'                   => 'vitrine',
                    'page'                        => 'vitrine-builder-translations',
                    'vitrine_export_translations' => '1',
                ),
                admin_url( 'edit.php' )
            ),
            'vitrine_export_translations'
        );
        ?>
        <div class="wrap vitrine-translations-wrap">
            <h1><?php echo esc_html( Vitrine_I18n::t( 'Builder translations', 'ui.translations_page_title' ) ); ?></h1>
            <p class="description"><?php echo esc_html( Vitrine_I18n::t( 'Override element names and builder interface strings per language. Works with Polylang and standard WordPress translations (.po/.mo).', 'ui.translations_page_intro' ) ); ?></p>
            <?php if ( Vitrine_Polylang::is_active() ) : ?>
                <p class="description"><?php echo esc_html( Vitrine_I18n::t( 'Strings are also registered in Polylang → String translations (group: Builder Vitrine).', 'ui.polylang_strings_hint' ) ); ?></p>
            <?php endif; ?>
            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( Vitrine_I18n::t( 'Translations saved.', 'ui.translations_saved' ) ); ?></p></div>
            <?php endif; ?>
            <?php if ( $import_ok ) : ?>
                <div class="notice notice-success is-dismissible"><p>
                    <?php
                    echo esc_html( Vitrine_I18n::t( 'Translations imported from CSV.', 'ui.translations_import_success' ) );
                    if ( $import_count > 0 ) {
                        echo ' ';
                        echo esc_html( sprintf(
                            /* translators: 1: number of strings, 2: language slugs */
                            __( '%1$d strings · languages: %2$s', 'builder-vitrine' ),
                            $import_count,
                            $import_langs ? $import_langs : '—'
                        ) );
                    }
                    ?>
                </p></div>
            <?php elseif ( $import_error ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html( Vitrine_I18n::t( 'Could not import the CSV file.', 'ui.translations_import_error' ) ); ?></p></div>
            <?php endif; ?>

            <div class="vitrine-translations-import card" style="max-width:720px;padding:16px 20px;margin:20px 0;">
                <h2 style="margin-top:0;"><?php echo esc_html( Vitrine_I18n::t( 'Import CSV', 'ui.translations_import_title' ) ); ?></h2>
                <p class="description"><?php echo esc_html( Vitrine_I18n::t( 'Upload a translations CSV (Key, Default text, Translation English, Translation Español, Translation Português) or load the file bundled with the plugin.', 'ui.translations_import_intro' ) ); ?></p>

                <form method="post" enctype="multipart/form-data" style="margin-top:12px;">
                    <?php wp_nonce_field( 'vitrine_import_translations', 'vitrine_translations_import_nonce' ); ?>
                    <p>
                        <label for="vitrine_trans_csv"><strong><?php echo esc_html( Vitrine_I18n::t( 'Upload CSV', 'ui.translations_import_upload' ) ); ?></strong></label><br />
                        <input type="file" id="vitrine_trans_csv" name="vitrine_trans_csv" accept=".csv,text/csv" />
                    </p>
                    <p>
                        <strong><?php echo esc_html( Vitrine_I18n::t( 'Import mode', 'ui.translations_import_mode' ) ); ?></strong><br />
                        <label style="display:block;margin:6px 0;">
                            <input type="radio" name="vitrine_trans_import_replace" value="0" checked />
                            <?php echo esc_html( Vitrine_I18n::t( 'Merge (keep existing, fill/update from CSV)', 'ui.translations_import_merge' ) ); ?>
                        </label>
                        <label style="display:block;margin:6px 0;">
                            <input type="radio" name="vitrine_trans_import_replace" value="1" />
                            <?php echo esc_html( Vitrine_I18n::t( 'Replace languages found in the CSV', 'ui.translations_import_replace' ) ); ?>
                        </label>
                    </p>
                    <p class="submit" style="margin-bottom:0;">
                        <button type="submit" name="vitrine_trans_import_source" value="upload" class="button button-primary">
                            <?php echo esc_html( Vitrine_I18n::t( 'Upload CSV', 'ui.translations_import_upload' ) ); ?>
                        </button>
                        <?php if ( $bundled_exists ) : ?>
                            <button type="submit" name="vitrine_trans_import_source" value="bundled" class="button">
                                <?php echo esc_html( Vitrine_I18n::t( 'Load plugin CSV', 'ui.translations_import_bundled' ) ); ?>
                            </button>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url( $export_url ); ?>">
                            <?php echo esc_html( Vitrine_I18n::t( 'Export CSV', 'ui.translations_export' ) ); ?>
                        </a>
                    </p>
                </form>
            </div>

            <form method="get" action="" style="margin:16px 0;">
                <input type="hidden" name="post_type" value="vitrine" />
                <input type="hidden" name="page" value="vitrine-builder-translations" />
                <label for="vitrine-trans-lang-select"><strong><?php echo esc_html( Vitrine_I18n::t( 'Language', 'ui.translations_language' ) ); ?></strong></label>
                <select id="vitrine-trans-lang-select" name="lang" onchange="this.form.submit()">
                    <?php foreach ( $languages as $slug => $label ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $current_lang, $slug ); ?>><?php echo esc_html( $label . ' (' . $slug . ')' ); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <form method="post" action="">
                <?php wp_nonce_field( 'vitrine_save_translations', 'vitrine_translations_nonce' ); ?>
                <input type="hidden" name="vitrine_trans_lang" value="<?php echo esc_attr( $current_lang ); ?>" />
                <p>
                    <input type="search" id="vitrine-trans-filter" class="regular-text" placeholder="<?php echo esc_attr( Vitrine_I18n::t( 'Filter strings…', 'ui.translations_filter' ) ); ?>" />
                </p>
                <table class="widefat striped vitrine-translations-table">
                    <thead>
                        <tr>
                            <th style="width:22%"><?php echo esc_html( Vitrine_I18n::t( 'Key', 'ui.translations_key' ) ); ?></th>
                            <th style="width:28%"><?php echo esc_html( Vitrine_I18n::t( 'Default text', 'ui.translations_default' ) ); ?></th>
                            <th><?php echo esc_html( Vitrine_I18n::t( 'Translation', 'ui.translations_value' ) ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $catalog as $key => $default ) : ?>
                            <?php
                            $value = isset( $overrides[ $current_lang ][ $key ] ) ? $overrides[ $current_lang ][ $key ] : '';
                            ?>
                            <tr class="vitrine-trans-row" data-key="<?php echo esc_attr( $key ); ?>" data-default="<?php echo esc_attr( $default ); ?>">
                                <td><code><?php echo esc_html( $key ); ?></code></td>
                                <td><?php echo esc_html( $default ); ?></td>
                                <td>
                                    <input type="text" class="large-text" name="vitrine_trans_value[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $default ); ?>" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php echo esc_html( Vitrine_I18n::t( 'Save translations', 'ui.translations_save' ) ); ?></button>
                </p>
            </form>
        </div>
        <script>
        (function () {
            var input = document.getElementById('vitrine-trans-filter');
            if (!input) return;
            input.addEventListener('input', function () {
                var q = (input.value || '').toLowerCase();
                document.querySelectorAll('.vitrine-trans-row').forEach(function (row) {
                    var key = (row.getAttribute('data-key') || '').toLowerCase();
                    var def = (row.getAttribute('data-default') || '').toLowerCase();
                    var val = '';
                    var field = row.querySelector('input');
                    if (field) val = (field.value || '').toLowerCase();
                    row.style.display = (!q || key.indexOf(q) !== -1 || def.indexOf(q) !== -1 || val.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        })();
        </script>
        <?php
    }
}
