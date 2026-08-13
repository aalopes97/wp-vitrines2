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
        $s = wp_parse_args( $settings, $this->defaults() );

        $items       = is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
        $n           = count( $items );
        $center_size = max( 60, intval( $s['center_size'] ) );
        $radius      = max( 80, intval( $s['radius'] ) );
        $icon_size   = max( 16, intval( $s['icon_size'] ) );
        $icon_color  = esc_attr( $s['icon_color'] );
        $card_bg     = esc_attr( $s['card_bg'] );
        $accent      = esc_attr( ! empty( $s['card_border'] ) ? $s['card_border'] : ( ! empty( $s['line_color'] ) ? $s['line_color'] : '#2e7d32' ) );
        $title_color = esc_attr( $s['title_color'] );
        $text_color  = esc_attr( $s['text_color'] );
        $bg_color    = esc_attr( $s['bg_color'] );
        $center_bg   = esc_attr( $s['center_bg_color'] );
        $center_lbl  = esc_html( $s['center_label'] );
        $card_style      = $this->sanitize_card_style( $s['card_style'] );
        $use_preset      = 'default' !== $card_style;
        $layout          = $this->compute_orbit_layout( $radius, $center_size, $card_style, $n );

        $r_pct      = $layout['r_pct'];
        $cs_pct_w   = $layout['cs_pct'];
        $card_max_w = $layout['card_max_w'];
        $dense      = ! empty( $layout['dense'] );

        $wrap_style  = 'background:' . $bg_color
            . ';--a2-accent:' . $accent
            . ';--a2-card-max-w:' . $card_max_w . '%'
            . ';--a2-card-min-h:0px;';

        if ( $use_preset ) {
            $wrap_style .= $this->build_card_preset_style( $s, $icon_color );
        }

        $root_class = 'vitrine-el-aranha2 vitrine-card-style--' . esc_attr( $card_style );
        if ( $dense ) {
            $root_class .= ' is-dense';
        }

        $output  = '<div class="' . esc_attr( $root_class ) . '" style="' . esc_attr( $wrap_style ) . '" data-a2-compact-auto="1" data-a2-items="' . (int) $n . '">';
        $output .= '<div class="vitrine-aranha2__fit">';
        $output .= '<div class="vitrine-aranha2__stage">';

        $output .= '<div class="vitrine-aranha2__center"'
            . ' style="width:' . $cs_pct_w . '%;'
            . 'border-color:' . $accent . ';'
            . 'background-color:' . $center_bg . ';">';

        if ( ! empty( $s['center_image'] ) ) {
            $output .= '<img src="' . esc_url( $s['center_image'] ) . '" alt="' . esc_attr( $center_lbl ) . '" />';
        } elseif ( $center_lbl ) {
            $output .= '<span class="vitrine-aranha2__center-label" style="color:' . $text_color . ';">'
                . $center_lbl . '</span>';
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

            $title = isset( $item['title'] ) ? wp_kses_post( $item['title'] ) : '';
            $text  = isset( $item['text'] )  ? wp_kses_post( $item['text'] )  : '';
            if ( ! $title && $text ) {
                $title = $text;
                $text  = '';
            }
            $icon  = isset( $item['icon'] ) ? $item['icon'] : '';
            $link  = isset( $item['link'] ) ? esc_url( $item['link'] ) : '';
            $delay = number_format( $i * 0.08 + 0.15, 2 );
            $card_class = 'vitrine-aranha2__card'
                . ( $link ? ' vitrine-aranha2__card--linked' : '' )
                . ( $use_preset ? ' vitrine-card-style-' . esc_attr( $card_style ) : '' );

            $card_style_attr = 'left:' . $x_pct . '%;top:' . $y_pct . '%;animation-delay:' . $delay . 's;';
            if ( ! $use_preset ) {
                $card_style_attr .= '--a2-card-bg:' . $card_bg . ';'
                    . '--a2-card-border:' . $accent . ';'
                    . '--a2-card-text:' . $text_color . ';';
            }

            $output .= '<div class="' . esc_attr( $card_class ) . '" style="' . esc_attr( $card_style_attr ) . '">';

            $inner = '';

            if ( $icon ) {
                $icon_wrap = $use_preset ? 'vitrine-card-icon' : 'vitrine-aranha2__card-icon';
                $inner .= '<span class="' . esc_attr( $icon_wrap ) . '">'
                    . $this->render_icon( $icon, $icon_size, $icon_color ) . '</span>';
            }

            if ( $title || $text ) {
                $content_class = $use_preset ? 'vitrine-card-content' : 'vitrine-aranha2__card-content';
                $inner .= '<div class="' . esc_attr( $content_class ) . '">';
                if ( $title ) {
                    $inner .= '<h3 class="vitrine-aranha2__card-title" style="color:' . $title_color . ';">' . $title . '</h3>';
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

            $output .= '</div>'; // card
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
     * Layout orbital em % do stage — um único círculo limpo.
     * A largura do card é limitada pelo número de itens para não encavalar.
     * Com muitos itens, marca dense (só ícone + título; texto no hover).
     *
     * @param string $card_style default|dark|white|border-left
     * @return array{r_pct:float,cs_pct:float,card_max_w:float,dense:bool}
     */
    private function compute_orbit_layout( $radius_px, $center_size_px, $card_style, $n_items ) {
        $n_items    = max( 0, intval( $n_items ) );
        $ref        = 720;
        $card_style = $this->sanitize_card_style( $card_style );
        $preset     = 'default' !== $card_style;
        $dense      = $n_items >= 6;

        // Alvos de largura (serão reduzidos se não couberem no círculo).
        if ( 'border-left' === $card_style || 'white' === $card_style ) {
            $want_w = 26.0;
            $card_h = $dense ? 10.0 : 12.0;
        } elseif ( 'dark' === $card_style ) {
            $want_w = 24.0;
            $card_h = $dense ? 14.0 : 18.0;
        } else {
            $want_w = 18.0;
            $card_h = $dense ? 12.0 : 16.0;
        }

        // Com densos, cards mais compactos permitem um pouco mais de largura.
        if ( $dense && $preset ) {
            $want_w = min( 28.0, $want_w + 2.0 );
        }

        $gap    = 2.0 + min( 3.0, $n_items * 0.2 );
        $cs_pct = max( 10.0, min( 22.0, ( max( 60, intval( $center_size_px ) ) / $ref ) * 100 ) );
        // Centro menor quando há muitos itens — sobra espaço para o anel.
        if ( $n_items >= 8 ) {
            $cs_pct = min( $cs_pct, 18.0 );
        }

        $r_user = max( 12.0, min( 42.0, ( max( 80, intval( $radius_px ) ) / $ref ) * 100 ) );
        $card_w = $want_w;

        if ( $n_items > 1 ) {
            $sin_half = sin( M_PI / $n_items );
            // Raio máximo para o card caber no stage (centro do card).
            $max_r = 47.0 - ( $card_w / 2 );

            // Largura máxima que ainda cabe no círculo com esse max_r.
            if ( $sin_half > 0.001 ) {
                $fit_w = max( 12.0, ( 2 * $sin_half * $max_r ) - $gap );
                $card_w = min( $card_w, $fit_w );
                // Recalcula max_r com a largura final.
                $max_r = 47.0 - ( $card_w / 2 );

                $need_r = ( $card_w + $gap ) / ( 2 * $sin_half );
                $need_r = max( $need_r, ( $card_h + $gap ) / ( 2 * $sin_half ) );
            } else {
                $need_r = $r_user;
                $max_r  = 47.0 - ( $card_w / 2 );
            }
        } else {
            $need_r = $r_user;
            $max_r  = 47.0 - ( $card_w / 2 );
        }

        // Folga do centro (imagem principal livre).
        $clear_center = ( $cs_pct / 2 ) + ( $card_h / 2 ) + 5.0;
        $r_pct        = max( $r_user, $need_r, $clear_center );
        $r_pct        = min( $r_pct, $max_r );

        // Se ainda encavalaria no centro, reduz centro.
        if ( $r_pct < $clear_center ) {
            $cs_pct = max( 10.0, 2 * ( $r_pct - ( $card_h / 2 ) - 5.0 ) );
            $clear_center = ( $cs_pct / 2 ) + ( $card_h / 2 ) + 5.0;
            $r_pct = max( $r_pct, min( $max_r, $clear_center ) );
        }

        return array(
            'r_pct'      => round( $r_pct, 4 ),
            'cs_pct'     => round( $cs_pct, 4 ),
            'card_max_w' => round( $card_w, 2 ),
            'dense'      => $dense,
        );
    }

    private function sanitize_card_style( $style ) {
        $allowed = array( 'default', 'dark', 'white', 'border-left' );
        $style   = sanitize_key( $style );
        return in_array( $style, $allowed, true ) ? $style : 'default';
    }

    private function render_icon( $icon, $icon_size, $icon_color = '' ) {
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
        return '<img src="' . esc_url( $icon ) . '" alt=""'
            . ' style="width:' . $icon_size . 'px;height:' . $icon_size . 'px;object-fit:contain;border-radius:4px;" />';
    }
}

// Mantido apenas como motor de render do elemento unificado "aranha".
// Vitrine_Element_Registry::register( new Vitrine_Element_Aranha2() );
