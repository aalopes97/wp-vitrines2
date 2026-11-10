<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Button extends Vitrine_Element {

    public function slug() {
        return 'button';
    }

    public function label() {
        return 'Botão';
    }

    public function icon() {
        return 'dashicons-button';
    }

    public function defaults() {
        return array(
            'text'     => 'Clique aqui',
            'url'      => '#',
            'bg_color' => '#0073aa',
            'color'    => '#ffffff',
            'align'    => 'center',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'text',     'label' => 'Texto',           'type' => 'text' ),
            array( 'name' => 'url',      'label' => 'Link (URL)',      'type' => 'text' ),
            array( 'name' => 'bg_color', 'label' => 'Cor de fundo',   'type' => 'color' ),
            array( 'name' => 'color',    'label' => 'Cor do texto',   'type' => 'color' ),
            array( 'name' => 'align',    'label' => 'Alinhamento',    'type' => 'select', 'options' => array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $defaults = $this->defaults();
        $s        = wp_parse_args( $settings, $defaults );

        $align = in_array( $s['align'], array( 'left', 'center', 'right' ), true )
            ? $s['align']
            : 'center';
        $wrapper_style = sprintf( 'text-align:%s;', esc_attr( $align ) );
        $btn_style     = sprintf(
            'background:%s;color:%s;padding:12px 28px;border:none;border-radius:4px;font-size:16px;cursor:pointer;display:inline-block;text-decoration:none;',
            esc_attr( $s['bg_color'] ),
            esc_attr( $s['color'] )
        );

        return sprintf(
            '<div class="vitrine-el-button" style="%s"><a href="%s" style="%s">%s</a></div>',
            $wrapper_style,
            esc_url( $s['url'] ),
            $btn_style,
            esc_html( $s['text'] )
        );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Button() );
