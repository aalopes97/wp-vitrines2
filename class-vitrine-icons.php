<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Listas completas de ícones para o picker do editor.
 */
class Vitrine_Icons {

    const FA_VERSION = '6.7.2';

    /**
     * URL do CSS local do Font Awesome Free.
     *
     * @return string
     */
    public static function fontawesome_css_url() {
        return VITRINE_URL . 'assets/vendor/fontawesome/css/all.min.css';
    }

    /**
     * Enfileira Font Awesome Free (bundled no plugin).
     *
     * @param string[] $deps Dependências do style handle.
     */
    public static function enqueue_fontawesome( $deps = array() ) {
        wp_enqueue_style(
            'font-awesome',
            self::fontawesome_css_url(),
            $deps,
            self::FA_VERSION
        );
    }

    /**
     * Todos os Dashicons registrados no core do WordPress.
     *
     * @return string[]
     */
    public static function get_dashicons() {
        static $list = null;
        if ( null !== $list ) {
            return $list;
        }

        $css_file = ABSPATH . WPINC . '/css/dashicons.css';
        if ( ! is_readable( $css_file ) ) {
            $list = array();
            return $list;
        }

        $content = file_get_contents( $css_file );
        if ( ! $content || ! preg_match_all( '/\.dashicons-([a-z0-9-]+):before\s*\{/', $content, $matches ) ) {
            $list = array();
            return $list;
        }

        $names = array_unique( $matches[1] );
        sort( $names, SORT_STRING );

        $list = array();
        foreach ( $names as $name ) {
            if ( 'before' === $name ) {
                continue;
            }
            $list[] = 'dashicons-' . $name;
        }

        return $list;
    }

    /**
     * Ícones Font Awesome Free (solid, regular, brands) — lista bundlada no plugin.
     *
     * @return string[] Ex.: "fas fa-user", "fab fa-github"
     */
    public static function get_fontawesome() {
        static $list = null;
        if ( null !== $list ) {
            return $list;
        }

        $bundled = VITRINE_PATH . 'assets/vendor/fontawesome/icons-list.php';
        if ( is_readable( $bundled ) ) {
            $icons = include $bundled;
            if ( is_array( $icons ) && ! empty( $icons ) ) {
                $list = array_values( $icons );
                return $list;
            }
        }

        // Fallback: tenta transient / CDN (ambientes legados).
        $cached = get_transient( 'vitrine_fa_icons_list' );
        if ( is_array( $cached ) && ! empty( $cached ) ) {
            $list = $cached;
            return $list;
        }

        $list = array();
        return $list;
    }

    /**
     * Dados para wp_localize_script.
     *
     * @return array{dashicons: string[], fontawesome: string[]}
     */
    public static function get_picker_data() {
        return array(
            'dashicons'   => self::get_dashicons(),
            'fontawesome' => self::get_fontawesome(),
        );
    }
}
