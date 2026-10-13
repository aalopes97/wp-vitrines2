<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Textimagebuttons extends Vitrine_Element {

    public function slug() {
        return 'textimagebuttons';
    }

    public function label() {
        return 'Texto + Imagem + Botões';
    }

    public function icon() {
        return 'dashicons-button';
    }

    public function defaults() {
        return array(
            'title'             => 'Título do bloco',
            'title_tag'         => 'h2',
            'title_color'       => '#1a1a1a',
            'title_font_size'   => '28',
            'content'           => '<p>Escreva o conteúdo aqui. Use negrito, listas e links para formatar o texto.</p>',
            'content_color'     => '#555555',
            'content_font_size' => '16',
            'image'             => '',
            'image_position'    => 'right',
            'image_width'       => '42',
            'bg_color'          => '#f8f8f6',
            'padding'           => '32',
            'border_radius'     => '8',
            'gap'               => '24',
            'buttons_align'     => 'left',
            'buttons_gap'       => '12',
            'buttons_radius'    => '4',
            'btn1_text'         => 'Saiba mais',
            'btn1_url'          => '#',
            'btn1_target'       => '_self',
            'btn1_bg'           => '#0073aa',
            'btn1_color'        => '#ffffff',
            'btn2_text'         => '',
            'btn2_url'          => '',
            'btn2_target'       => '_self',
            'btn2_bg'           => '#1d2327',
            'btn2_color'        => '#ffffff',
            'btn3_text'         => '',
            'btn3_url'          => '',
            'btn3_target'       => '_self',
            'btn3_bg'           => '#2271b1',
            'btn3_color'        => '#ffffff',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'title',             'label' => 'Título',                  'type' => 'text' ),
            array( 'name' => 'title_tag',         'label' => 'Tag do título',           'type' => 'select', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4' ) ),
            array( 'name' => 'title_color',       'label' => 'Cor do título',           'type' => 'color' ),
            array( 'name' => 'title_font_size',   'label' => 'Tam. título (px)',        'type' => 'number' ),
            array( 'name' => 'content',           'label' => 'Texto',                   'type' => 'textarea' ),
            array( 'name' => 'content_color',     'label' => 'Cor do texto',            'type' => 'color' ),
            array( 'name' => 'content_font_size', 'label' => 'Tam. texto (px)',         'type' => 'number' ),
            array( 'name' => 'image',             'label' => 'Imagem',                  'type' => 'image' ),
            array( 'name' => 'image_position',    'label' => 'Posição da imagem',       'type' => 'select', 'options' => array( 'right' => 'Direita', 'left' => 'Esquerda' ) ),
            array( 'name' => 'image_width',       'label' => 'Largura imagem (%)',      'type' => 'number' ),
            array( 'name' => 'bg_color',          'label' => 'Cor de fundo',            'type' => 'color' ),
            array( 'name' => 'padding',           'label' => 'Espaçamento interno (px)', 'type' => 'number' ),
            array( 'name' => 'border_radius',     'label' => 'Arredondamento (px)',     'type' => 'number' ),
            array( 'name' => 'gap',               'label' => 'Espaço texto/imagem (px)', 'type' => 'number' ),
            array( 'name' => 'buttons_align',     'label' => 'Alinhamento dos botões',  'type' => 'select', 'options' => array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) ),
            array( 'name' => 'buttons_gap',       'label' => 'Espaço entre botões (px)', 'type' => 'number' ),
            array( 'name' => 'buttons_radius',    'label' => 'Arred. dos botões (px)',  'type' => 'number' ),
            array( 'name' => '_section_btn1',     'label' => 'Botão 1',                 'type' => 'heading' ),
            array( 'name' => 'btn1_text',         'label' => 'Texto do botão 1',        'type' => 'text' ),
            array( 'name' => 'btn1_url',          'label' => 'Link do botão 1',         'type' => 'text' ),
            array( 'name' => 'btn1_target',       'label' => 'Abrir botão 1',           'type' => 'select', 'options' => array( '_self' => 'Mesma aba', '_blank' => 'Nova aba' ) ),
            array( 'name' => 'btn1_bg',           'label' => 'Fundo do botão 1',        'type' => 'color' ),
            array( 'name' => 'btn1_color',        'label' => 'Texto do botão 1 (cor)',  'type' => 'color' ),
            array( 'name' => '_section_btn2',     'label' => 'Botão 2 (opcional)',      'type' => 'heading' ),
            array( 'name' => 'btn2_text',         'label' => 'Texto do botão 2',        'type' => 'text' ),
            array( 'name' => 'btn2_url',          'label' => 'Link do botão 2',         'type' => 'text' ),
            array( 'name' => 'btn2_target',       'label' => 'Abrir botão 2',           'type' => 'select', 'options' => array( '_self' => 'Mesma aba', '_blank' => 'Nova aba' ) ),
            array( 'name' => 'btn2_bg',           'label' => 'Fundo do botão 2',        'type' => 'color' ),
            array( 'name' => 'btn2_color',        'label' => 'Texto do botão 2 (cor)',  'type' => 'color' ),
            array( 'name' => '_section_btn3',     'label' => 'Botão 3 (opcional)',      'type' => 'heading' ),
            array( 'name' => 'btn3_text',         'label' => 'Texto do botão 3',        'type' => 'text' ),
            array( 'name' => 'btn3_url',          'label' => 'Link do botão 3',         'type' => 'text' ),
            array( 'name' => 'btn3_target',       'label' => 'Abrir botão 3',           'type' => 'select', 'options' => array( '_self' => 'Mesma aba', '_blank' => 'Nova aba' ) ),
            array( 'name' => 'btn3_bg',           'label' => 'Fundo do botão 3',        'type' => 'color' ),
            array( 'name' => 'btn3_color',        'label' => 'Texto do botão 3 (cor)',  'type' => 'color' ),
        );
    }

    /**
     * Até 3 botões com texto preenchido.
     *
     * @param array $s
     * @return array
     */
    private function collect_buttons( $s ) {
        $buttons = array();
        for ( $i = 1; $i <= 3; $i++ ) {
            $text = isset( $s[ 'btn' . $i . '_text' ] ) ? trim( (string) $s[ 'btn' . $i . '_text' ] ) : '';
            if ( '' === $text ) {
                continue;
            }
            $target = isset( $s[ 'btn' . $i . '_target' ] ) ? sanitize_key( $s[ 'btn' . $i . '_target' ] ) : '_self';
            if ( '_blank' !== $target ) {
                $target = '_self';
            }
            $buttons[] = array(
                'text'   => $text,
                'url'    => isset( $s[ 'btn' . $i . '_url' ] ) ? trim( (string) $s[ 'btn' . $i . '_url' ] ) : '',
                'target' => $target,
                'bg'     => ! empty( $s[ 'btn' . $i . '_bg' ] ) ? $s[ 'btn' . $i . '_bg' ] : '#0073aa',
                'color'  => ! empty( $s[ 'btn' . $i . '_color' ] ) ? $s[ 'btn' . $i . '_color' ] : '#ffffff',
            );
        }
        return $buttons;
    }

    public function render( $settings, $children_html = '' ) {
        $s = wp_parse_args( $settings, $this->defaults() );

        $allowed_tags = array( 'h2', 'h3', 'h4' );
        $title_tag    = in_array( $s['title_tag'], $allowed_tags, true ) ? $s['title_tag'] : 'h2';

        $title             = esc_html( $s['title'] );
        $title_color       = esc_attr( $s['title_color'] );
        $title_font_size   = max( 12, intval( $s['title_font_size'] ) ) . 'px';
        $content           = wp_kses_post( $s['content'] );
        $content_color     = esc_attr( $s['content_color'] );
        $content_font_size = max( 10, intval( $s['content_font_size'] ) ) . 'px';
        $image             = $s['image'] ? esc_url( $s['image'] ) : '';
        $image_width       = max( 20, min( 60, intval( $s['image_width'] ) ) );
        $text_width        = 100 - $image_width;
        $bg_color          = esc_attr( $s['bg_color'] );
        $padding           = max( 0, intval( $s['padding'] ) ) . 'px';
        $border_radius     = max( 0, intval( $s['border_radius'] ) ) . 'px';
        $gap               = max( 0, intval( $s['gap'] ) ) . 'px';
        $image_position    = ( 'left' === $s['image_position'] ) ? 'left' : 'right';
        $buttons_align     = in_array( $s['buttons_align'], array( 'left', 'center', 'right' ), true ) ? $s['buttons_align'] : 'left';
        $buttons_gap       = max( 0, intval( $s['buttons_gap'] ) ) . 'px';
        $buttons_radius    = max( 0, intval( $s['buttons_radius'] ) ) . 'px';
        $buttons           = $this->collect_buttons( $s );

        $align_map = array(
            'left'   => 'flex-start',
            'center' => 'center',
            'right'  => 'flex-end',
        );
        $btn_justify = $align_map[ $buttons_align ];

        $wrap_style = sprintf(
            'background:%s;padding:%s;border-radius:%s;--ti-gap:%s;--ti-img-w:%s%%;--ti-text-w:%s%%;--tib-btn-gap:%s;--tib-btn-radius:%s;--tib-btn-align:%s;',
            $bg_color,
            $padding,
            $border_radius,
            $gap,
            $image_width,
            $text_width,
            $buttons_gap,
            $buttons_radius,
            $btn_justify
        );

        $inner_class = 'vitrine-textimage-inner';
        if ( 'left' === $image_position ) {
            $inner_class .= ' vitrine-textimage-inner--image-left';
        }

        $text_html  = '<div class="vitrine-textimage-content">';
        if ( $title ) {
            $text_html .= sprintf(
                '<%1$s class="vitrine-textimage-title" style="color:%2$s;font-size:%3$s;">%4$s</%1$s>',
                $title_tag,
                $title_color,
                $title_font_size,
                $title
            );
        }
        if ( $content ) {
            $text_html .= sprintf(
                '<div class="vitrine-textimage-body" style="color:%1$s;font-size:%2$s;">%3$s</div>',
                $content_color,
                $content_font_size,
                $content
            );
        }
        if ( $buttons ) {
            $text_html .= '<div class="vitrine-tib-actions vitrine-tib-actions--' . esc_attr( $buttons_align ) . '">';
            foreach ( $buttons as $btn ) {
                $btn_style = sprintf(
                    'background:%s;color:%s;border-radius:%s;',
                    esc_attr( $btn['bg'] ),
                    esc_attr( $btn['color'] ),
                    $buttons_radius
                );
                if ( $btn['url'] ) {
                    $rel = '_blank' === $btn['target'] ? ' rel="noopener noreferrer"' : '';
                    $text_html .= sprintf(
                        '<a class="vitrine-tib-btn" href="%s" target="%s"%s style="%s">%s</a>',
                        esc_url( $btn['url'] ),
                        esc_attr( $btn['target'] ),
                        $rel,
                        esc_attr( $btn_style ),
                        esc_html( $btn['text'] )
                    );
                } else {
                    $text_html .= sprintf(
                        '<span class="vitrine-tib-btn" style="%s">%s</span>',
                        esc_attr( $btn_style ),
                        esc_html( $btn['text'] )
                    );
                }
            }
            $text_html .= '</div>';
        }
        $text_html .= '</div>';

        $media_html = '<div class="vitrine-textimage-media">';
        if ( $image ) {
            $media_html .= '<img src="' . $image . '" alt="" class="vitrine-textimage-img" />';
        } else {
            $media_html .= '<div class="vitrine-textimage-placeholder"><span class="dashicons dashicons-format-image"></span></div>';
        }
        $media_html .= '</div>';

        $output  = '<div class="vitrine-el-textimage vitrine-el-textimagebuttons" style="' . esc_attr( $wrap_style ) . '">';
        $output .= '<div class="' . esc_attr( $inner_class ) . '">';
        $output .= $text_html . $media_html;
        $output .= '</div></div>';

        return $output;
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Textimagebuttons() );
