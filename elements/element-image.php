<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Image extends Vitrine_Element {

    public function slug() {
        return 'image';
    }

    public function label() {
        return 'Imagem';
    }

    public function icon() {
        return 'dashicons-format-image';
    }

    public function defaults() {
        return array(
            'url'   => '',
            'alt'   => '',
            'align' => 'center',
            'width' => '100',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'url',   'label' => 'Imagem',        'type' => 'image' ),
            array( 'name' => 'alt',   'label' => 'Texto alt',     'type' => 'text' ),
            array( 'name' => 'align', 'label' => 'Alinhamento',   'type' => 'select', 'options' => array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) ),
            array( 'name' => 'width', 'label' => 'Largura (%)',   'type' => 'number' ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $defaults = $this->defaults();
        $s        = wp_parse_args( $settings, $defaults );

        if ( empty( $s['url'] ) ) {
            return '<p class="vitrine-el-image--empty">Nenhuma imagem selecionada.</p>';
        }

        $align = in_array( $s['align'], array( 'left', 'center', 'right' ), true )
            ? $s['align']
            : 'center';
        $justify = array(
            'left'   => 'flex-start',
            'center' => 'center',
            'right'  => 'flex-end',
        );
        $style = 'display:flex;justify-content:' . $justify[ $align ] . ';';

        $img_style = sprintf( 'max-width:%s%%;height:auto;', intval( $s['width'] ) );

        return sprintf(
            '<div class="vitrine-el-image" style="%s"><img src="%s" alt="%s" style="%s" /></div>',
            $style,
            esc_url( $s['url'] ),
            esc_attr( $s['alt'] ),
            $img_style
        );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Image() );
