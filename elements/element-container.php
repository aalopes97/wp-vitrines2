<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Container extends Vitrine_Element {

    public function slug() {
        return 'container';
    }

    public function label() {
        return 'Container';
    }

    public function icon() {
        return 'dashicons-grid-view';
    }

    public function defaults() {
        return array(
            'name'          => '',
            'bg_color'      => '#f5f5f5',
            'bg_image'      => '',
            'bg_size'       => 'cover',
            'padding'       => '20',
            'max_width'     => '1200',
            'full_width_bg' => '0',
            'direction'     => 'column',
            'gap'           => '16',
            'align_items'   => 'stretch',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'name',          'label' => 'Nome do container',               'type' => 'text' ),
            array( 'name' => 'bg_color',      'label' => 'Cor de fundo',                    'type' => 'color' ),
            array( 'name' => 'bg_image',      'label' => 'Imagem de fundo',                 'type' => 'image' ),
            array( 'name' => 'bg_size',       'label' => 'Tamanho do fundo',                'type' => 'select', 'options' => array( 'cover' => 'Cobrir (cover)', 'contain' => 'Conter (contain)', 'auto' => 'Automático' ) ),
            array( 'name' => 'full_width_bg', 'label' => 'Fundo largura total',             'type' => 'select', 'options' => array( '0' => 'Não', '1' => 'Sim (conteúdo em 1200px)' ) ),
            array( 'name' => 'padding',       'label' => 'Padding (px)',                    'type' => 'number' ),
            array( 'name' => 'max_width',     'label' => 'Largura máx conteúdo (px)',       'type' => 'number' ),
            array( 'name' => 'direction',     'label' => 'Direção',                         'type' => 'select', 'options' => array( 'column' => 'Linha (um abaixo do outro)', 'row' => 'Coluna (lado a lado)' ) ),
            array( 'name' => 'gap',           'label' => 'Espaço entre itens (px)',         'type' => 'number' ),
            array( 'name' => 'align_items',   'label' => 'Alinhar itens',                   'type' => 'select', 'options' => array( 'stretch' => 'Esticar', 'flex-start' => 'Topo', 'center' => 'Centro', 'flex-end' => 'Base' ) ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $s = wp_parse_args( $settings, $this->defaults() );
        $is_nested = ! empty( $s['_nested'] ) && '1' === (string) $s['_nested'];

        $direction   = in_array( $s['direction'], array( 'column', 'row' ), true ) ? $s['direction'] : 'column';
        $align_items = in_array( $s['align_items'], array( 'stretch', 'flex-start', 'center', 'flex-end' ), true ) ? $s['align_items'] : 'stretch';
        $flex_wrap   = ( 'row' === $direction ) ? 'nowrap' : 'wrap';
        $padding     = max( 0, intval( $s['padding'] ) );
        $max_width   = max( 320, intval( $s['max_width'] ) );
        $full_width  = ! empty( $s['full_width_bg'] ) && '0' !== (string) $s['full_width_bg'];

        $bg_image_style = $this->build_bg_image_style( $s );

        $inner_flex = sprintf(
            'display:flex;flex-direction:%s;gap:%spx;align-items:%s;flex-wrap:%s;',
            $direction,
            intval( $s['gap'] ),
            $align_items,
            $flex_wrap
        );

        $width_constraint = $is_nested
            ? 'width:100%;max-width:100%;margin-left:0;margin-right:0;'
            : sprintf( 'max-width:%spx;margin:0 auto;', $max_width );

        $class = 'vitrine-el-container vitrine-container--reveal vitrine-el-container--' . esc_attr( $direction );
        if ( $is_nested ) {
            $class .= ' vitrine-el-container--nested';
        }
        if ( $full_width ) {
            $class .= ' vitrine-el-container--full';
            if ( $is_nested ) {
                $class .= ' vitrine-el-container--full-contained';
            }

            if ( $is_nested ) {
                $outer_style = sprintf(
                    'background-color:%s;%swidth:100%%;max-width:100%%;padding:%spx 0;box-sizing:border-box;',
                    esc_attr( $s['bg_color'] ),
                    $bg_image_style,
                    $padding
                );

                $inner_style = sprintf(
                    'width:100%%;max-width:100%%;margin:0;padding:0 %spx;box-sizing:border-box;%s',
                    $padding,
                    $inner_flex
                );

                return sprintf(
                    '<div class="%1$s"><div class="vitrine-container-full" style="%2$s"><div class="vitrine-container-inner" style="%3$s">%4$s</div></div></div>',
                    $class,
                    esc_attr( $outer_style ),
                    esc_attr( $inner_style ),
                    $children_html
                );
            }

            $outer_style = sprintf(
                'background-color:%s;%spadding:%spx 0;box-sizing:border-box;',
                esc_attr( $s['bg_color'] ),
                $bg_image_style,
                $padding
            );

            $inner_style = sprintf(
                'max-width:%spx;margin:0 auto;padding:0 %spx;box-sizing:border-box;%s',
                $max_width,
                $padding,
                $inner_flex
            );

            return sprintf(
                '<div class="%1$s"><div class="vitrine-container-full" style="%2$s"><div class="vitrine-container-inner" style="%3$s">%4$s</div></div></div>',
                $class,
                esc_attr( $outer_style ),
                esc_attr( $inner_style ),
                $children_html
            );
        }

        $style = sprintf(
            'background-color:%s;%spadding:%spx;box-sizing:border-box;%s%s',
            esc_attr( $s['bg_color'] ),
            $bg_image_style,
            $padding,
            $width_constraint,
            $inner_flex
        );

        return sprintf(
            '<div class="%1$s" style="%2$s">%3$s</div>',
            $class,
            esc_attr( $style ),
            $children_html
        );
    }

    private function build_bg_image_style( $s ) {
        if ( empty( $s['bg_image'] ) ) {
            return '';
        }
        $allowed_sizes = array( 'cover', 'contain', 'auto' );
        $bg_size       = in_array( $s['bg_size'], $allowed_sizes, true ) ? $s['bg_size'] : 'cover';

        return sprintf(
            'background-image:url(%s);background-size:%s;background-position:center;background-repeat:no-repeat;',
            esc_url( $s['bg_image'] ),
            $bg_size
        );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Container() );
