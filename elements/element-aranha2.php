<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Aranha2 extends Vitrine_Element {

    public function slug() {
        return 'aranha2';
    }

    public function label() {
        return 'Aranha Circular';
    }

    public function icon() {
        return 'dashicons-chart-pie';
    }

    public function defaults() {
        return array_merge( array(
            'center_image'    => '',
            'center_size'     => '160',
            'center_label'    => '',
            'center_bg_color' => '#ffffff',
            'bg_color'        => '#f8f9fa',
            'card_bg'         => '#ffffff',
            'card_border'     => '#2e7d32',
            'title_color'     => '#1d2327',
            'text_color'      => '#555555',
            'icon_size'       => '36',
            'icon_color'      => '#2e7d32',
            'radius'          => '200',
            'card_style'      => 'default',
            'card_min_height' => '190',
            'items'           => array(),
        ), self::card_preset_defaults() );
    }

    public function fields() {
        return array_merge( array(
            array( 'name' => 'center_image',    'label' => 'Imagem Central',        'type' => 'image' ),
            array( 'name' => 'center_size',     'label' => 'Tamanho central (px)',  'type' => 'number' ),
            array( 'name' => 'center_label',    'label' => 'Rótulo central',        'type' => 'text' ),
            array( 'name' => 'center_bg_color', 'label' => 'Cor de fundo centro',   'type' => 'color' ),
            array( 'name' => 'radius',          'label' => 'Raio orbital (px)',     'type' => 'number' ),
            array( 'name' => 'icon_size',       'label' => 'Tam. ícones (px)',      'type' => 'number' ),
            array( 'name' => 'icon_color',      'label' => 'Cor dos ícones',        'type' => 'color' ),
            array( 'name' => 'card_style',      'label' => 'Modelo do card',        'type' => 'select', 'options' => array(
                'default'     => 'Padrão (orbital)',
                'dark'        => 'Escuro (ícone acima)',
                'white'       => 'Branco (ícone ao lado)',
                'border-left' => 'Borda esquerda',
            ) ),
            array( 'name' => 'card_min_height', 'label' => 'Altura mínima dos cards (px)', 'type' => 'number' ),
            array( 'name' => 'card_bg',         'label' => 'Cor fundo card',        'type' => 'color' ),
            array( 'name' => 'card_border',     'label' => 'Cor destaque (bordas)', 'type' => 'color' ),
            array( 'name' => 'title_color',     'label' => 'Cor do título',         'type' => 'color' ),
            array( 'name' => 'text_color',      'label' => 'Cor do texto',          'type' => 'color' ),
            array( 'name' => 'bg_color',        'label' => 'Cor de fundo',          'type' => 'color' ),
        ), self::card_preset_fields() );
    }

    public function render( $settings, $children_html = '' ) {
        $s = Vitrine_Layout::normalize_aranha_settings(
            wp_parse_args( $settings, $this->defaults() )
        );

        $items = is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
        $items = array_values(
            array_filter(
                $items,
                function ( $item ) {
                    if ( ! is_array( $item ) ) {
                        return false;
                    }
                    $title = isset( $item['title'] ) ? trim( wp_strip_all_tags( (string) $item['title'] ) ) : '';
                    $text  = isset( $item['text'] ) ? trim( wp_strip_all_tags( (string) $item['text'] ) ) : '';
                    $icon  = isset( $item['icon'] ) ? trim( (string) $item['icon'] ) : '';
                    return ( '' !== $title || '' !== $text || '' !== $icon );
                }
            )
        );
        $n           = count( $items );
        $center_size = max( 60, min( 560, intval( $s['center_size'] ) ) );
        $radius      = max( 80, intval( $s['radius'] ) );
        $icon_size   = max( 16, intval( $s['icon_size'] ) );
        $icon_color  = esc_attr( $s['icon_color'] );
        $card_bg     = esc_attr( $s['card_bg'] );
        $accent      = esc_attr( ! empty( $s['card_border'] ) ? $s['card_border'] : ( ! empty( $s['line_color'] ) ? $s['line_color'] : '#2e7d32' ) );
        $title_color = esc_attr( $s['title_color'] );
        $text_color  = esc_attr( $s['text_color'] );
        $bg_color    = esc_attr( $s['bg_color'] );
        $center_bg   = esc_attr( $s['center_bg_color'] );
        $center_lbl  = sanitize_text_field( $s['center_label'] );
        $card_style = $this->sanitize_card_style( $s['card_style'] );
        $use_preset = 'default' !== $card_style;
        $layout     = $this->compute_orbit_layout( $radius, $center_size, $card_style, $n );
        $r_pct      = $layout['r_pct'];
        $cs_pct_w   = $layout['cs_pct'];
        $card_max_w = $layout['card_max_w'];
        $is_linear  = ! empty( $layout['linear'] );
        $card_min_h = $use_preset ? max( 0, min( 420, intval( $s['card_min_height'] ) ) ) : 0;

        $wrap_style  = 'background:' . $bg_color
            . ';--a2-accent:' . $accent
            . ';--a2-center-size:' . $center_size . 'px'
            . ';--a2-card-max-w:' . $card_max_w . '%'
            . ';--a2-card-min-h:' . $card_min_h . 'px;';

        if ( $use_preset ) {
            $wrap_style .= $this->build_card_preset_style( $s, $icon_color );
        }

        $root_class = 'vitrine-el-aranha2 vitrine-a2-circular vitrine-card-style--' . esc_attr( $card_style );
        if ( $is_linear ) {
            $root_class .= ' is-linear';
        }

        $output  = '<div class="' . esc_attr( $root_class ) . '" style="' . esc_attr( $wrap_style ) . '" data-a2-items="' . (int) $n . '">';
        $output .= '<div class="vitrine-aranha2__fit">';
        $output .= '<div class="vitrine-aranha2__stage">';

        if ( ! $is_linear && $n > 0 ) {
            $output .= '<svg class="vitrine-aranha2__connectors" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">';
            for ( $i = 0; $i < $n; $i++ ) {
                $angle = - M_PI / 2 + $i * ( 2 * M_PI / max( 1, $n ) );
                $x_pct = round( 50 + $r_pct * cos( $angle ), 4 );
                $y_pct = round( 50 + $r_pct * sin( $angle ), 4 );
                $output .= '<line x1="50" y1="50" x2="' . $x_pct . '" y2="' . $y_pct . '" />';
            }
            $output .= '</svg>';
        }

        $output .= '<div class="vitrine-aranha2__center"'
            . ' style="width:' . $center_size . 'px;'
            . 'max-width:' . $cs_pct_w . '%;'
            . 'border-color:' . $accent . ';'
            . 'background-color:' . $center_bg . ';">';

        if ( ! empty( $s['center_image'] ) ) {
            $center_fit = isset( $s['center_image_fit'] ) && 'contain' === $s['center_image_fit'] ? 'contain' : 'cover';
            $output .= '<img src="' . esc_url( $s['center_image'] ) . '" alt="' . esc_attr( $center_lbl ) . '" style="object-fit:' . esc_attr( $center_fit ) . ';" />';
        } elseif ( $center_lbl ) {
            $output .= '<span class="vitrine-aranha2__center-label" style="color:' . $text_color . ';">'
                . esc_html( $center_lbl ) . '</span>';
        } else {
            $output .= '<span class="vitrine-aranha2__center-placeholder">'
                . '<span class="dashicons dashicons-camera"></span></span>';
        }

        $output .= '</div>'; // center

        // ── Cards ─────────────────────────────────────────────────────────
        for ( $i = 0; $i < $n; $i++ ) {
            $item  = $items[ $i ];
            $angle = - M_PI / 2 + $i * ( 2 * M_PI / max( 1, $n ) );
            $x_pct = round( 50 + $r_pct * cos( $angle ), 4 );
            $y_pct = round( 50 + $r_pct * sin( $angle ), 4 );

            $title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
            $text  = isset( $item['text'] ) ? wp_kses_post( $item['text'] ) : '';
            $icon  = isset( $item['icon'] ) ? $item['icon'] : '';
            $link  = isset( $item['link'] ) ? esc_url( $item['link'] ) : '';
            $alt   = isset( $item['alt'] ) ? sanitize_text_field( $item['alt'] ) : $title;
            $card_class = 'vitrine-aranha2__card'
                . ( $link ? ' vitrine-aranha2__card--linked' : '' )
                . ( $use_preset ? ' vitrine-card-style-' . esc_attr( $card_style ) : '' );

            $card_style_attr = $is_linear
                ? ''
                : 'left:' . $x_pct . '%;top:' . $y_pct . '%;';
            $card_style_attr .= $card_min_h ? 'min-height:' . $card_min_h . 'px;' : '';
            if ( ! $use_preset ) {
                $card_style_attr .= '--a2-card-bg:' . $card_bg . ';'
                    . '--a2-card-border:' . $accent . ';'
                    . '--a2-card-text:' . $text_color . ';';
            }

            $output .= '<article class="' . esc_attr( $card_class ) . '" style="' . esc_attr( $card_style_attr ) . '" data-item-id="' . esc_attr( $item['id'] ) . '">';

            $inner = '';

            if ( $icon ) {
                $icon_wrap = $use_preset ? 'vitrine-card-icon' : 'vitrine-aranha2__card-icon';
                $inner .= '<span class="' . esc_attr( $icon_wrap ) . '" aria-hidden="' . ( filter_var( $icon, FILTER_VALIDATE_URL ) ? 'false' : 'true' ) . '">'
                    . $this->render_icon( $icon, $icon_size, $icon_color, $alt ) . '</span>';
            }

            if ( $title || $text ) {
                $content_class = $use_preset ? 'vitrine-card-content' : 'vitrine-aranha2__card-content';
                $inner .= '<div class="' . esc_attr( $content_class ) . '">';
                if ( $title ) {
                    $inner .= '<h3 class="vitrine-aranha2__card-title" style="color:' . $title_color . ';">' . esc_html( $title ) . '</h3>';
                }
                if ( $text ) {
                    $inner .= '<div class="vitrine-aranha2__card-text" style="color:' . $text_color . ';">' . $text . '</div>';
                }
                $inner .= '</div>';
            }

            if ( $link ) {
                $output .= '<a href="' . $link . '" class="vitrine-aranha2__card-link">' . $inner . '</a>';
            } else {
                $output .= $inner;
            }

            $output .= '</article>'; // card
        }

        // Empty state
        if ( ! $n ) {
            $output .= '<div class="vitrine-aranha2__empty">'
                . '<span class="dashicons dashicons-chart-pie"></span>'
                . '<p>Adicione itens no painel de configurações</p>'
                . '</div>';
        }

        $output .= '</div>'; // stage
        $output .= '</div>'; // fit
        $output .= '</div>'; // el-aranha2

        return $output;
    }

    /**
     * Calcula um orbital previsível. No desktop a órbita é mantida;
     * o empilhamento linear fica apenas no CSS mobile.
     *
     * @param string $card_style default|dark|white|border-left
     * @return array{r_pct:float,cs_pct:float,card_max_w:float,linear:bool}
     */
    private function compute_orbit_layout( $radius_px, $center_size_px, $card_style, $n_items ) {
        $n_items    = max( 0, intval( $n_items ) );
        $card_style = $this->sanitize_card_style( $card_style );
        $linear     = false;
        $center_px  = max( 60, min( 560, intval( $center_size_px ) ) );
        // Espelho proporcional do tamanho em px (ref 720), até ~55% do stage.
        $cs_pct     = max( 10.0, min( 55.0, ( $center_px / 720 ) * 100 ) );
        $r_user     = max( 28.0, min( 42.0, ( max( 100, intval( $radius_px ) ) / 720 ) * 100 ) );
        $card_w     = 'border-left' === $card_style || 'white' === $card_style ? 22.0 : 20.0;

        if ( $n_items > 1 ) {
            $card_w = min( $card_w, max( 11.0, ( 100 / $n_items ) * 1.15 ) );
        }
        if ( $n_items >= 7 ) {
            $card_w = min( $card_w, 14.0 );
            $r_user = max( $r_user, 34.0 );
        }

        $card_h       = $n_items >= 7 ? 10.0 : 12.0;
        $clear_center = ( $cs_pct / 2 ) + ( $card_h / 2 ) + 4.0;
        $r_pct        = max( $r_user, $clear_center );
        $r_pct        = min( $r_pct, 47.0 - ( $card_w / 2 ) );

        return array(
            'r_pct'      => round( $r_pct, 4 ),
            'cs_pct'     => round( $cs_pct, 4 ),
            'card_max_w' => round( $card_w, 2 ),
            'linear'     => $linear,
        );
    }

    private function sanitize_card_style( $style ) {
        $allowed = array( 'default', 'dark', 'white', 'border-left' );
        $style   = sanitize_key( $style );
        return in_array( $style, $allowed, true ) ? $style : 'default';
    }

    private function render_icon( $icon, $icon_size, $icon_color = '', $alt = '' ) {
        if ( ! $icon ) {
            return '';
        }
        $color_style = $icon_color ? 'color:' . esc_attr( $icon_color ) . ';' : '';
        if ( strpos( $icon, 'dashicons-' ) === 0 ) {
            return '<span class="dashicons ' . esc_attr( $icon )
                . '" style="font-size:' . $icon_size . 'px;width:' . $icon_size . 'px;height:' . $icon_size . 'px;' . $color_style . '"></span>';
        }
        if ( preg_match( '/^fa[srlbd]?\s/', $icon ) ) {
            return '<i class="' . esc_attr( $icon ) . '" style="font-size:' . $icon_size . 'px;' . $color_style . '"></i>';
        }
        return '<img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $alt ) . '"'
            . ' style="width:' . $icon_size . 'px;height:' . $icon_size . 'px;object-fit:contain;border-radius:4px;" />';
    }
}

// Fallback para instalações que ainda não carregaram o elemento unificado.
// Em instalações atuais, o adapter não aparece duplicado na paleta.
if ( ! Vitrine_Element_Registry::get( 'aranha' ) ) {
    Vitrine_Element_Registry::register( new Vitrine_Element_Aranha2() );
}
