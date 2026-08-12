<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Title extends Vitrine_Element {

    public function slug() {
        return 'title';
    }

    public function label() {
        return 'Título';
    }

    public function icon() {
        return 'dashicons-heading';
    }

    public function defaults() {
        return array(
            'text'       => 'Título da Vitrine',
            'tag'        => 'h2',
            'align'      => 'left',
            'color'      => '#333333',
            'font_size'  => '28',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'text',      'label' => 'Texto',         'type' => 'text' ),
            array( 'name' => 'tag',       'label' => 'Tag HTML',      'type' => 'select', 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ) ),
            array( 'name' => 'align',     'label' => 'Alinhamento',   'type' => 'select', 'options' => array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) ),
            array( 'name' => 'color',     'label' => 'Cor',           'type' => 'color' ),
            array( 'name' => 'font_size', 'label' => 'Tamanho (px)',  'type' => 'number' ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $defaults = $this->defaults();
        $s        = wp_parse_args( $settings, $defaults );

        $allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
        $tag          = in_array( $s['tag'], $allowed_tags, true ) ? $s['tag'] : 'h2';

        $style = sprintf(
            'margin:0;padding:0;text-align:%s;color:%s;font-size:%spx;line-height:1.25;',
            esc_attr( $s['align'] ),
            esc_attr( $s['color'] ),
            intval( $s['font_size'] )
        );

        return sprintf(
            '<%1$s class="vitrine-el-title" style="%2$s">%3$s</%1$s>',
            $tag,
            $style,
            esc_html( $s['text'] )
        );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Title() );
