<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Render {

    public function __construct() {
        add_filter( 'the_content', array( $this, 'render_vitrine' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
    }

    /**
     * Substitui o conteúdo do post vitrine pelo layout renderizado.
     */
    public function render_vitrine( $content ) {
        if ( ! is_singular( 'vitrine' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $layout  = get_post_meta( $post_id, '_vitrine_layout', true );

        if ( empty( $layout ) || ! is_array( $layout ) ) {
            return '<p>Nenhum conteúdo na vitrine.</p>';
        }

        $elements = Vitrine_Plugin::load_elements();
        $output   = '<div class="vitrine-front">';
        $output  .= $this->render_items( $layout, $elements );
        $output  .= '</div>';
        return $output;
    }

    /**
     * Renderiza itens do layout recursivamente (suporta containers com filhos).
     */
    private function render_items( $items, $elements, $depth = 0 ) {
        $output = '';

        foreach ( $items as $item ) {
            $type = isset( $item['type'] ) ? $item['type'] : '';
            if ( ! isset( $elements[ $type ] ) ) {
                continue;
            }
            $settings = isset( $item['settings'] ) ? $item['settings'] : array();
            $height   = isset( $item['height'] ) ? absint( $item['height'] ) : 0;
            $width    = isset( $item['width'] ) ? $item['width'] : '';

            if ( $depth > 0 && 'container' === $type ) {
                $settings['_nested'] = '1';
            }

            $inline_styles = array();
            if ( $height ) {
                $inline_styles[] = 'min-height:' . $height . 'px';
            }
            if ( $width ) {
                $inline_styles[] = 'flex:0 1 ' . esc_attr( $width );
                $inline_styles[] = 'max-width:' . esc_attr( $width );
                $inline_styles[] = 'min-width:0';
                $inline_styles[] = 'box-sizing:border-box';
            } elseif ( $depth > 0 && 'container' === $type ) {
                $inline_styles[] = 'width:100%';
                $inline_styles[] = 'max-width:100%';
                $inline_styles[] = 'min-width:0';
                $inline_styles[] = 'box-sizing:border-box';
            }
            $custom_css = isset( $settings['custom_css'] ) ? $settings['custom_css'] : '';
            if ( $custom_css ) {
                // Sanitiza: remove tags e permite apenas propriedades CSS inline
                $custom_css = wp_strip_all_tags( $custom_css );
                $custom_css = str_replace( array( '<', '>', '"' ), '', $custom_css );
                $inline_styles[] = $custom_css;
            }

            if ( 'aranha2' === $type ) {
                $inline_styles[] = 'position:relative';
                $inline_styles[] = 'z-index:auto';
                $inline_styles[] = 'overflow:hidden';
            }

            $style = $inline_styles ? ' style="' . esc_attr( implode( ';', $inline_styles ) ) . '"' : '';

            // Renderiza filhos (para containers)
            $children_html = '';
            if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
                $children_html = $this->render_items( $item['children'], $elements, $depth + 1 );
            }

            $output .= '<div class="vitrine-block vitrine-block--' . esc_attr( $type ) . '"' . $style . '>';
            $output .= $elements[ $type ]->render( $settings, $children_html );
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Enfileira CSS do frontend apenas quando necessário.
     */
    public function enqueue_frontend() {
        if ( ! is_singular( 'vitrine' ) ) {
            return;
        }
        Vitrine_Icons::enqueue_fontawesome();

        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style(
            'vitrine-front-css',
            VITRINE_URL . 'assets/css/frontend.css',
            array( 'font-awesome', 'dashicons' ),
            file_exists( VITRINE_PATH . 'assets/css/frontend.css' )
                ? filemtime( VITRINE_PATH . 'assets/css/frontend.css' )
                : VITRINE_VERSION
        );

        wp_enqueue_script(
            'vitrine-front-js',
            VITRINE_URL . 'assets/js/frontend.js',
            array(),
            file_exists( VITRINE_PATH . 'assets/js/frontend.js' )
                ? filemtime( VITRINE_PATH . 'assets/js/frontend.js' )
                : VITRINE_VERSION,
            true
        );

        $page_settings = get_post_meta( get_the_ID(), '_vitrine_page_settings', true );
        if ( is_array( $page_settings ) && ! empty( $page_settings['custom_css'] ) ) {
            $custom_css = $this->sanitize_page_custom_css( $page_settings['custom_css'] );
            if ( $custom_css ) {
                wp_add_inline_style( 'vitrine-front-css', $custom_css );
            }
        }
    }

    /**
     * Sanitiza CSS personalizado da vitrine.
     */
    private function sanitize_page_custom_css( $css ) {
        $css = wp_strip_all_tags( (string) $css );
        $css = preg_replace( '/expression\s*\(/i', '', $css );
        $css = preg_replace( '/javascript\s*:/i', '', $css );
        $css = str_replace( array( '<', '>' ), '', $css );
        return trim( $css );
    }
}
