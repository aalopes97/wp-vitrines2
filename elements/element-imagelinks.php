<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Imagelinks extends Vitrine_Element {

    public function slug() {
        return 'imagelinks';
    }

    public function label() {
        return 'Imagem + Links';
    }

    public function icon() {
        return 'dashicons-images-alt2';
    }

    public function defaults() {
        return array(
            'image'               => '',
            'image_height'        => '220',
            'image_fit'           => 'cover',
            'caption'             => '',
            'caption_bg'          => '#000000',
            'caption_color'       => '#ffffff',
            'caption_font_size'   => '16',
            'box_bg'              => '#e8e8e8',
            'box_title'           => '',
            'box_title_color'     => '#333333',
            'box_title_font_size' => '16',
            'content'             => '',
            'content_color'       => '#333333',
            'content_font_size'   => '14',
            'content_heading_size'=> '',
            'content_link_size'   => '',
            'content_link_color'  => '',
            'content_link_underline' => 'underline',
            'separator_color'     => '#333333',
            'box_padding'         => '16',
            'media_radius_top'    => '0',
            'box_radius_bottom'   => '0',
        );
    }

    public function fields() {
        return array(
            array( 'name' => 'image',               'label' => 'Imagem',                      'type' => 'image' ),
            array( 'name' => 'image_height',        'label' => 'Altura da imagem (px)',       'type' => 'number' ),
            array( 'name' => 'image_fit',           'label' => 'Ajuste da imagem',            'type' => 'select', 'options' => array( 'cover' => 'Cover (preenche)', 'contain' => 'Contain (inteira)' ) ),
            array( 'name' => 'caption',             'label' => 'Legenda (faixa preta)',       'type' => 'text' ),
            array( 'name' => 'caption_bg',          'label' => 'Fundo da legenda',            'type' => 'color' ),
            array( 'name' => 'caption_color',       'label' => 'Cor da legenda',              'type' => 'color' ),
            array( 'name' => 'caption_font_size',   'label' => 'Tam. legenda (px)',           'type' => 'number' ),
            array( 'name' => 'box_bg',              'label' => 'Fundo da caixa de conteúdo',  'type' => 'color' ),
            array( 'name' => 'box_title',           'label' => 'Título da caixa',             'type' => 'text' ),
            array( 'name' => 'box_title_color',     'label' => 'Cor do título',               'type' => 'color' ),
            array( 'name' => 'box_title_font_size', 'label' => 'Tam. título (px)',            'type' => 'number' ),
            array( 'name' => 'content',             'label' => 'Conteúdo abaixo da imagem',   'type' => 'textarea' ),
            array( 'name' => 'content_color',       'label' => 'Cor do texto',                'type' => 'color' ),
            array( 'name' => 'content_font_size',   'label' => 'Tam. texto (px)',             'type' => 'number' ),
            array( 'name' => 'content_heading_size','label' => 'Tam. títulos no conteúdo (px)', 'type' => 'number' ),
            array( 'name' => 'content_link_size',   'label' => 'Tam. links (px)',             'type' => 'number' ),
            array( 'name' => 'content_link_color',  'label' => 'Cor dos links',               'type' => 'color' ),
            array( 'name' => 'content_link_underline', 'label' => 'Sublinhado dos links',    'type' => 'select', 'options' => array( 'inherit' => 'Herdar do texto', 'underline' => 'Sublinhado', 'none' => 'Sem sublinhado' ) ),
            array( 'name' => 'separator_color',     'label' => 'Cor do separador',            'type' => 'color' ),
            array( 'name' => 'box_padding',         'label' => 'Espaçamento interno (px)',    'type' => 'number' ),
            array( 'name' => 'media_radius_top',    'label' => 'Arred. superior da imagem (px)', 'type' => 'number' ),
            array( 'name' => 'box_radius_bottom',   'label' => 'Arred. inferior da caixa (px)', 'type' => 'number' ),
        );
    }

    public function render( $settings, $children_html = '' ) {
        $s = wp_parse_args( $settings, $this->defaults() );

        $image             = $s['image'] ? esc_url( $s['image'] ) : '';
        $image_height      = max( 0, intval( $s['image_height'] ) );
        $image_fit         = ( 'contain' === $s['image_fit'] ) ? 'contain' : 'cover';
        $caption           = esc_html( $s['caption'] );
        $caption_bg        = esc_attr( $s['caption_bg'] );
        $caption_color     = esc_attr( $s['caption_color'] );
        $caption_font_size = max( 10, intval( $s['caption_font_size'] ) ) . 'px';
        $box_bg            = esc_attr( $s['box_bg'] );
        $box_title         = esc_html( $s['box_title'] );
        $box_title_color   = esc_attr( $s['box_title_color'] );
        $box_title_size    = max( 10, intval( $s['box_title_font_size'] ) ) . 'px';
        $content_color     = esc_attr( isset( $s['content_color'] ) ? $s['content_color'] : ( isset( $s['link_color'] ) ? $s['link_color'] : '#333333' ) );
        $content_font_size = max( 10, intval( isset( $s['content_font_size'] ) ? $s['content_font_size'] : ( isset( $s['link_font_size'] ) ? $s['link_font_size'] : 14 ) ) ) . 'px';
        $separator_color   = esc_attr( $s['separator_color'] );
        $box_padding       = max( 0, intval( $s['box_padding'] ) ) . 'px';
        $media_radius_top  = max( 0, intval( $s['media_radius_top'] ) ) . 'px';
        $box_radius_bottom = max( 0, intval( $s['box_radius_bottom'] ) ) . 'px';
        $content_heading_size = ( '' !== $s['content_heading_size'] && null !== $s['content_heading_size'] )
            ? max( 8, intval( $s['content_heading_size'] ) ) . 'px' : '';
        $content_link_size = ( '' !== $s['content_link_size'] && null !== $s['content_link_size'] )
            ? max( 8, intval( $s['content_link_size'] ) ) . 'px' : '';
        $content_link_color = ! empty( $s['content_link_color'] ) ? esc_attr( $s['content_link_color'] ) : 'inherit';
        $link_underline = isset( $s['content_link_underline'] ) ? sanitize_key( $s['content_link_underline'] ) : 'underline';
        if ( ! in_array( $link_underline, array( 'inherit', 'underline', 'none' ), true ) ) {
            $link_underline = 'underline';
        }
        $content_html      = $this->get_content_html( $s );

        $wrap_style = '--il-img-h:' . $image_height . 'px;'
            . '--il-img-fit:' . $image_fit . ';'
            . '--il-caption-bg:' . $caption_bg . ';'
            . '--il-caption-color:' . $caption_color . ';'
            . '--il-caption-size:' . $caption_font_size . ';'
            . '--il-box-bg:' . $box_bg . ';'
            . '--il-box-title-color:' . $box_title_color . ';'
            . '--il-box-title-size:' . $box_title_size . ';'
            . '--il-content-color:' . $content_color . ';'
            . '--il-content-size:' . $content_font_size . ';'
            . '--il-separator-color:' . $separator_color . ';'
            . '--il-box-padding:' . $box_padding . ';'
            . '--il-media-radius-top:' . $media_radius_top . ';'
            . '--il-box-radius-bottom:' . $box_radius_bottom . ';'
            . ( $content_heading_size ? '--il-content-h-size:' . $content_heading_size . ';' : '' )
            . ( $content_link_size ? '--il-link-size:' . $content_link_size . ';' : '' )
            . '--il-link-color:' . $content_link_color . ';'
            . '--il-link-decoration:' . ( 'inherit' === $link_underline ? 'inherit' : $link_underline ) . ';';

        $output  = '<div class="vitrine-el-imagelinks" style="' . esc_attr( $wrap_style ) . '">';
        $output .= '<div class="vitrine-imagelinks-hero">';

        $output .= '<div class="vitrine-imagelinks-media"' . ( $image_height > 0 ? ' style="height:' . $image_height . 'px;min-height:' . $image_height . 'px;"' : ' style="height:0;min-height:0;"' ) . '>';
        if ( $image ) {
            $img_style = 'object-fit:' . $image_fit . ';object-position:center;width:100%;height:100%;display:block;';
            $output   .= '<img src="' . $image . '" alt="" class="vitrine-imagelinks-img" style="' . esc_attr( $img_style ) . '" />';
        } else {
            $output .= '<div class="vitrine-imagelinks-placeholder"><span class="dashicons dashicons-format-image"></span></div>';
        }
        $output .= '</div>';

        if ( $caption ) {
            $output .= '<div class="vitrine-imagelinks-caption" style="background:' . $caption_bg . ';color:' . $caption_color . ';font-size:' . $caption_font_size . ';">'
                . $caption
                . '</div>';
        }

        $output .= '</div>';

        if ( $box_title || $content_html ) {
            $output .= '<div class="vitrine-imagelinks-box" style="background:' . $box_bg . ';padding:' . $box_padding . ';">';

            if ( $box_title ) {
                $output .= '<h3 class="vitrine-imagelinks-box-title" style="color:' . $box_title_color . ';font-size:' . $box_title_size . ';">'
                    . $box_title
                    . '</h3>';
            }

            if ( $box_title && $content_html ) {
                $output .= '<hr class="vitrine-imagelinks-separator" style="border-color:' . $separator_color . ';" />';
            }

            if ( $content_html ) {
                $output .= '<div class="vitrine-imagelinks-content" style="color:' . $content_color . ';font-size:' . $content_font_size . ';">'
                    . wp_kses_post( $content_html )
                    . '</div>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Conteúdo livre (WYSIWYG) ou conversão de layouts antigos com lista de links.
     */
    private function get_content_html( $settings ) {
        if ( ! empty( $settings['content'] ) ) {
            return $settings['content'];
        }

        if ( empty( $settings['items'] ) || ! is_array( $settings['items'] ) ) {
            return '';
        }

        $lines = array();
        foreach ( $settings['items'] as $link_item ) {
            $label = isset( $link_item['label'] ) ? trim( (string) $link_item['label'] ) : '';
            $url   = isset( $link_item['url'] ) ? trim( (string) $link_item['url'] ) : '';
            if ( ! $label ) {
                continue;
            }
            if ( $url ) {
                $lines[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
            } else {
                $lines[] = esc_html( $label );
            }
        }

        return implode( '<br />', $lines );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Imagelinks() );
