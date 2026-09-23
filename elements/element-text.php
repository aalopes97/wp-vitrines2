<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Text extends Vitrine_Element {

    public function slug() {
        return 'text';
    }

    public function label() {
        return 'Texto';
    }

    public function icon() {
        return 'dashicons-editor-paragraph';
    }

    public function defaults() {
        return array(
            'content'   => 'Escreva seu texto aqui...',
            'align'     => 'left',
            'color'     => '#555555',
            'font_size' => '16',
            'bg_color'  => '',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'content',   'label' => 'Conteúdo',       'type' => 'textarea' ),
            array( 'name' => 'align',     'label' => 'Alinhamento',    'type' => 'select', 'options' => array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) ),
            array( 'name' => 'color',     'label' => 'Cor',            'type' => 'color' ),
            array( 'name' => 'bg_color',  'label' => 'Cor de fundo',   'type' => 'color' ),
            array( 'name' => 'font_size', 'label' => 'Tamanho (px)',   'type' => 'number' ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $defaults = $this->defaults();
        $s        = wp_parse_args( $settings, $defaults );

        $color     = esc_attr( $s['color'] );
        $font_size = max( 8, intval( $s['font_size'] ) );
        $align     = in_array( $s['align'], array( 'left', 'center', 'right', 'justify' ), true )
            ? $s['align']
            : 'left';

        $style = 'text-align:' . $align
            . ';--vitrine-text-color:' . $color
            . ';color:' . $color
            . ';--vitrine-text-size:' . $font_size . 'px'
            . ';font-size:' . $font_size . 'px;';

        if ( ! empty( $s['bg_color'] ) ) {
            $style .= 'background:' . esc_attr( $s['bg_color'] ) . ';';
        }

        return sprintf(
            '<div class="vitrine-el-text" style="%s">%s</div>',
            $style,
            wp_kses_post( $s['content'] )
        );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Text() );
