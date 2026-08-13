<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Aranha3 extends Vitrine_Element {

    public function slug() {
        return 'aranha3';
    }

    public function label() {
        return 'Aranha Grade';
    }

    public function icon() {
        return 'dashicons-grid-view';
    }

    public function defaults() {
        return array_merge( array(
            'center_image'       => '',
            'center_image_fit'   => 'cover',
            'center_size'        => '240',
            'columns'            => '3',
            'card_bg'            => '#ffffff',
            'title_color'        => '#2c3a1a',
            'text_color'         => '#555555',
            'bg_color'           => '#f4f4f2',
            'border_radius'      => '12',
            'card_border_radius' => '12',
            'card_border_style'  => 'none',
            'card_border_width'  => '1',
            'card_border_color'  => '#d0d0d0',
            'image_border_radius'=> '12',
            'card_shadow'        => '6',
            'center_bg_color'    => '#d0d8c4',
            'center_bg_opacity'  => '100',
            'image_shadow'       => '0',
            'gap'                => '16',
            'icon_size'          => '32',
            'icon_color'         => '#2c3a1a',
            'card_height'        => '140',
            'card_text_align'    => 'top',
            'card_style'         => 'default',
            'card_min_height'    => '190',
            'wrapper_padding'    => '28',
            'wrapper_border_style' => 'none',
            'items'              => array(),
        ), self::card_preset_defaults() );
    }

    public function fields() {
        return array_merge( array(
            array( 'name' => 'center_image',        'label' => 'Imagem Central',              'type' => 'image' ),
            array( 'name' => 'center_image_fit',    'label' => 'Ajuste da imagem central',    'type' => 'select', 'options' => array( 'cover' => 'Cover (preenche)', 'contain' => 'Contain (inteira)' ) ),
            array( 'name' => 'center_size',         'label' => 'Tamanho imagem central (px)', 'type' => 'number' ),
            array( 'name' => 'columns',             'label' => 'Colunas (referência)',        'type' => 'number' ),
            array( 'name' => 'card_bg',             'label' => 'Fundo do card',               'type' => 'color' ),
            array( 'name' => 'title_color',         'label' => 'Cor do título',               'type' => 'color' ),
            array( 'name' => 'text_color',          'label' => 'Cor do texto',                'type' => 'color' ),
            array( 'name' => 'bg_color',            'label' => 'Cor de fundo',                'type' => 'color' ),
            array( 'name' => 'card_border_radius',  'label' => 'Arredondamento cards (px)',   'type' => 'number' ),
            array( 'name' => 'card_border_style',  'label' => 'Borda dos cards',             'type' => 'select', 'options' => array( 'none' => 'Nenhuma', 'solid' => 'Sólida', 'dashed' => 'Tracejada', 'dotted' => 'Pontilhada', 'double' => 'Dupla' ) ),
            array( 'name' => 'card_border_width',  'label' => 'Largura da borda (px)',       'type' => 'number' ),
            array( 'name' => 'card_border_color',  'label' => 'Cor da borda',                'type' => 'color' ),
            array( 'name' => 'image_border_radius', 'label' => 'Arredondamento imagem (px)',  'type' => 'number' ),
            array( 'name' => 'card_shadow',         'label' => 'Sombra dos cards',            'type' => 'range', 'min' => 0, 'max' => 100 ),
            array( 'name' => 'center_bg_color',       'label' => 'Fundo da imagem central',     'type' => 'color' ),
            array( 'name' => 'center_bg_opacity',   'label' => 'Opacidade do fundo (%)',      'type' => 'range', 'min' => 0, 'max' => 100 ),
            array( 'name' => 'image_shadow',        'label' => 'Sombra da imagem',            'type' => 'range', 'min' => 0, 'max' => 100 ),
            array( 'name' => 'gap',                 'label' => 'Espaçamento (px)',            'type' => 'number' ),
            array( 'name' => 'icon_size',           'label' => 'Tam. ícones (px)',            'type' => 'number' ),
            array( 'name' => 'icon_color',          'label' => 'Cor dos ícones',              'type' => 'color' ),
            array( 'name' => 'card_height',         'label' => 'Altura dos cards (px)',       'type' => 'number' ),
            array( 'name' => 'card_text_align',     'label' => 'Alinhamento do texto',      'type' => 'select', 'options' => array( 'top' => 'Topo', 'center' => 'Meio', 'bottom' => 'Base' ) ),
            array( 'name' => 'card_style',          'label' => 'Modelo do card',            'type' => 'select', 'options' => array(
                'default'     => 'Padrão',
                'dark'        => 'Escuro (ícone acima)',
                'white'       => 'Branco (ícone ao lado)',
                'border-left' => 'Borda esquerda',
            ) ),
            array( 'name' => 'card_min_height',     'label' => 'Altura mínima dos cards (px)', 'type' => 'number' ),
            array( 'name' => 'wrapper_padding',     'label' => 'Padding do bloco (px)',        'type' => 'number' ),
            array( 'name' => 'wrapper_border_style', 'label' => 'Borda do bloco',              'type' => 'select', 'options' => array( 'none' => 'Nenhuma', 'solid' => 'Sólida' ) ),
        ), self::card_preset_fields() );
    }

    public function render( $settings, $children_html = '' ) {
        $s = wp_parse_args( $settings, $this->defaults() );

        $center_image  = $s['center_image'] ? esc_url( $s['center_image'] ) : '';
        $columns       = max( 2, min( 6, intval( $s['columns'] ) ) );
        $card_bg            = esc_attr( $s['card_bg'] );
        $title_color        = esc_attr( $s['title_color'] );
        $text_color         = esc_attr( $s['text_color'] );
        $bg_color           = esc_attr( $s['bg_color'] );
        $card_border_radius = max( 0, intval( isset( $s['card_border_radius'] ) ? $s['card_border_radius'] : $s['border_radius'] ) );
        $card_border        = $this->resolve_card_border( $s );
        $image_border_radius = max( 0, intval( isset( $s['image_border_radius'] ) ? $s['image_border_radius'] : $s['border_radius'] ) );
        $card_shadow        = $this->build_box_shadow( $s['card_shadow'] );
        $card_shadow_hover  = $this->build_box_shadow( min( 100, intval( $s['card_shadow'] ) + 25 ) );
        $image_shadow       = $this->build_box_shadow( $s['image_shadow'] );
        $center_bg          = $this->hex_to_rgba( $s['center_bg_color'], $s['center_bg_opacity'] );
        $gap                = max( 0, intval( $s['gap'] ) );
        $icon_size          = max( 16, intval( $s['icon_size'] ) );
        $icon_color         = esc_attr( $s['icon_color'] );
        $card_height        = max( 80, intval( $s['card_height'] ) );
        $card_text_align    = $this->card_align_flex( $s['card_text_align'] );
        $center_size        = max( 120, min( 600, intval( $s['center_size'] ) ) );
        $center_image_fit   = ( isset( $s['center_image_fit'] ) && 'contain' === $s['center_image_fit'] ) ? 'contain' : 'cover';
        $core_max_width     = max( 220, min( 720, $center_size + 120 ) );
        $card_style         = $this->sanitize_card_style( $s['card_style'] );
        $use_preset         = 'default' !== $card_style;
        $card_min_height    = max( 80, intval( isset( $s['card_min_height'] ) ? $s['card_min_height'] : 190 ) );
        $card_height        = $use_preset ? $card_min_height : $card_height;
        $wrapper_padding    = max( 0, intval( isset( $s['wrapper_padding'] ) ? $s['wrapper_padding'] : 28 ) );
        $wrapper_border     = ( isset( $s['wrapper_border_style'] ) && 'solid' === $s['wrapper_border_style'] )
            ? 'border:1px solid #d0d0d0;'
            : 'border:none;';

        $items   = is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
        $n_items = count( $items );

        $layout = $this->compute_frame_layout( $items, $columns );
        $groups = $layout['groups'];

        $wrap_style = '--a3-gap:' . $gap . 'px;'
            . '--a3-card-h:' . $card_height . 'px;'
            . '--a3-img-h:' . $center_size . 'px;'
            . '--a3-img-fit:' . $center_image_fit . ';'
            . '--a3-core-max:' . $core_max_width . 'px;'
            . '--a3-card-shadow:' . $card_shadow . ';'
            . '--a3-card-shadow-hover:' . $card_shadow_hover . ';'
            . '--a3-card-align:' . $card_text_align . ';'
            . '--a3-card-border-width:' . $card_border['width'] . 'px;'
            . '--a3-card-border-style:' . $card_border['style'] . ';'
            . '--a3-card-border-color:' . $card_border['color'] . ';'
            . ( $bg_color ? 'background:' . $bg_color . ';' : '' )
            . 'padding:' . $wrapper_padding . 'px;'
            . $wrapper_border;

        if ( $use_preset ) {
            $wrap_style .= $this->build_card_preset_style( $s, $icon_color );
        }

        $output = '<div class="vitrine-el-aranha3 vitrine-a3--animate vitrine-card-style--' . esc_attr( $card_style ) . '" style="' . esc_attr( $wrap_style ) . '">';

        if ( $n_items <= 2 ) {
            $output .= '<div class="vitrine-a3-frame vitrine-a3-frame--inline">';
            $output .= $this->render_cards_group( $groups['left'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'side', $card_style, $use_preset );
            $output .= $this->render_image_cell( $center_image, $image_border_radius, $center_bg, $image_shadow, $center_size, $center_image_fit );
            $output .= $this->render_cards_group( $groups['right'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'side', $card_style, $use_preset );
            $output .= '</div>';
        } else {
            $output .= '<div class="vitrine-a3-frame vitrine-a3-frame--grid">';

            $output .= '<div class="vitrine-a3-band vitrine-a3-band--top">';
            $output .= $this->render_cards_group( $groups['top'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'band', $card_style, $use_preset );
            $output .= '</div>';

            $output .= '<div class="vitrine-a3-side vitrine-a3-side--left">';
            $output .= $this->render_cards_group( $groups['left'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'side', $card_style, $use_preset );
            $output .= '</div>';

            $output .= '<div class="vitrine-a3-core-image">';
            $output .= $this->render_image_cell( $center_image, $image_border_radius, $center_bg, $image_shadow, $center_size, $center_image_fit );
            $output .= '</div>';

            $output .= '<div class="vitrine-a3-side vitrine-a3-side--right">';
            $output .= $this->render_cards_group( $groups['right'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'side', $card_style, $use_preset );
            $output .= '</div>';

            $output .= '<div class="vitrine-a3-band vitrine-a3-band--bottom">';
            $output .= $this->render_cards_group( $groups['bottom'], $card_bg, $title_color, $text_color, $card_border_radius, $card_border['css'], $icon_size, $icon_color, $card_height, $card_text_align, 'band', $card_style, $use_preset );
            $output .= '</div>';

            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    private function render_image_cell( $center_image, $border_radius, $bg_color, $shadow, $center_size, $image_fit = 'cover' ) {
        $image_fit = ( 'contain' === $image_fit ) ? 'contain' : 'cover';
        $style = 'border-radius:' . $border_radius . 'px;'
            . 'background:' . esc_attr( $bg_color ) . ';'
            . 'box-shadow:' . esc_attr( $shadow ) . ';';
        $html  = '<div class="vitrine-a3-cell vitrine-a3-cell--image" style="' . esc_attr( $style ) . '">';
        if ( $center_image ) {
            $img_style = 'object-fit:' . $image_fit . ';object-position:center;width:100%;height:100%;display:block;';
            $html .= '<img src="' . $center_image . '" alt="" style="' . esc_attr( $img_style ) . '" />';
        } else {
            $html .= '<div class="vitrine-a3-img-placeholder">'
                . '<span class="dashicons dashicons-format-image"></span></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function render_cards_group( $items, $card_bg, $title_color, $text_color, $border_radius, $border_css, $icon_size, $icon_color, $card_height, $card_text_align, $context, $card_style = 'default', $use_preset = false ) {
        $html = '';
        foreach ( $items as $it ) {
            $html .= $this->render_card( $it, $card_bg, $title_color, $text_color, $border_radius, $border_css, $icon_size, $icon_color, $card_height, $card_text_align, $context, $card_style, $use_preset );
        }
        return $html;
    }

    /**
     * Renderiza um card do grid.
     */
    private function render_card( $it, $card_bg, $title_color, $text_color, $border_radius, $border_css, $icon_size, $icon_color, $card_height, $card_text_align, $context, $card_style = 'default', $use_preset = false ) {
        $title = isset( $it['title'] ) ? wp_kses_post( $it['title'] ) : '';
        $text  = isset( $it['text'] )  ? wp_kses_post( $it['text'] )  : '';
        $icon  = isset( $it['icon'] )  ? $it['icon']                   : '';
        $link  = isset( $it['link'] )  ? esc_url( $it['link'] )        : '';

        $cell_style = 'border-radius:' . $border_radius . 'px;'
            . 'box-sizing:border-box;display:flex;flex-direction:column'
            . ';justify-content:' . esc_attr( $card_text_align )
            . ';overflow:visible;width:100%;';

        if ( ! $use_preset ) {
            $cell_style = 'background:' . $card_bg . ';' . $cell_style . $border_css;
        }

        if ( 'side' === $context ) {
            $cell_style .= 'min-height:var(--a3-card-h, ' . (int) $card_height . 'px);height:auto;max-height:none;';
        } else {
            $cell_style .= 'min-height:var(--a3-card-h, ' . (int) $card_height . 'px);height:auto;max-height:none;flex:1 1 auto;min-width:0;';
        }

        $output = '<div class="vitrine-a3-cell vitrine-a3-cell--card'
            . ( $link ? ' vitrine-a3-cell--linked' : '' )
            . ( $use_preset ? ' vitrine-card-style-' . esc_attr( $card_style ) : '' ) . '"'
            . ' style="' . esc_attr( $cell_style ) . '">';

        $inner = '';

        if ( $icon || $title || $text ) {
            $inner .= '<div class="vitrine-a3-card-body">';

            if ( $icon ) {
                $icon_class = $use_preset ? 'vitrine-card-icon' : 'vitrine-a3-card-icon';
                $inner .= '<span class="' . esc_attr( $icon_class ) . '">'
                    . $this->render_icon( $icon, $icon_size, $icon_color ) . '</span>';
            }

            if ( $title || $text ) {
                $inner .= '<div class="vitrine-a3-card-content' . ( $use_preset ? ' vitrine-card-content' : '' ) . '">';
                if ( $title ) {
                    $inner .= '<h3 class="vitrine-a3-title"'
                        . ' style="color:' . $title_color . ';">' . $title . '</h3>';
                }
                if ( $text ) {
                    $inner .= '<div class="vitrine-a3-text"'
                        . ' style="color:' . $text_color . ';">' . $text . '</div>';
                }
                $inner .= '</div>';
            }

            $inner .= '</div>';
        }

        if ( $link ) {
            $output .= '<a href="' . $link . '" class="vitrine-a3-card-link">' . $inner . '</a>';
        } else {
            $output .= $inner;
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Layout em moldura 3×3: esquerda → topo → direita → base (aranha clássica).
     */
    private function compute_frame_layout( $items, $preferred_cols = 3 ) {
        $n_items = count( $items );
        $empty   = array( 'left' => array(), 'top' => array(), 'right' => array(), 'bottom' => array() );

        if ( $n_items <= 0 ) {
            return array( 'groups' => $empty );
        }

        if ( $n_items === 1 ) {
            return array( 'groups' => array(
                'left'   => array( $items[0] ),
                'top'    => array(),
                'right'  => array(),
                'bottom' => array(),
            ) );
        }

        if ( $n_items === 2 ) {
            return array( 'groups' => array(
                'left'   => array( $items[0] ),
                'top'    => array(),
                'right'  => array( $items[1] ),
                'bottom' => array(),
            ) );
        }

        $size  = $this->frame_grid_size( $n_items );
        $pools = $this->build_frame_pools( $size, $size, 0, 0 );
        $max   = array(
            'left'   => count( $pools['left'] ),
            'top'    => count( $pools['top'] ),
            'right'  => count( $pools['right'] ),
            'bottom' => count( $pools['bottom'] ),
        );

        return array( 'groups' => $this->assign_items_to_groups( $items, $max ) );
    }

    /** Coloca itens com posição explícita; preenche slots restantes na ordem aranha. */
    private function assign_items_to_groups( $items, $max ) {
        $groups      = array( 'left' => array(), 'top' => array(), 'right' => array(), 'bottom' => array() );
        $auto_items  = array();
        $allowed     = array( 'top', 'bottom', 'left', 'right' );

        foreach ( $items as $it ) {
            $pos = isset( $it['position'] ) ? sanitize_key( $it['position'] ) : 'auto';
            if ( in_array( $pos, $allowed, true ) && count( $groups[ $pos ] ) < $max[ $pos ] ) {
                $groups[ $pos ][] = $it;
            } else {
                $auto_items[] = $it;
            }
        }

        if ( empty( $auto_items ) ) {
            return $groups;
        }

        $remain_max = array(
            'left'   => max( 0, $max['left'] - count( $groups['left'] ) ),
            'top'    => max( 0, $max['top'] - count( $groups['top'] ) ),
            'right'  => max( 0, $max['right'] - count( $groups['right'] ) ),
            'bottom' => max( 0, $max['bottom'] - count( $groups['bottom'] ) ),
        );

        $counts = $this->distribute_frame_counts(
            count( $auto_items ),
            $remain_max['left'],
            $remain_max['top'],
            $remain_max['right'],
            $remain_max['bottom']
        );

        $order  = array( 'left', 'top', 'right', 'bottom' );
        $offset = 0;

        foreach ( $order as $side ) {
            $take = $counts[ $side ];
            if ( $take > 0 ) {
                $groups[ $side ] = array_merge( $groups[ $side ], array_slice( $auto_items, $offset, $take ) );
                $offset         += $take;
            }
        }

        return $groups;
    }

    /** Capacidade máxima de slots em moldura para grade N×N. */
    private function frame_grid_size( $n_items ) {
        if ( $n_items <= 8 ) {
            return 3;
        }
        $size = 5;
        while ( $this->frame_capacity( $size ) < $n_items ) {
            $size += 2;
        }
        return min( $size, 15 );
    }

    private function frame_capacity( $size ) {
        if ( $size <= 3 ) {
            return 8;
        }
        return 4 * ( $size - 2 );
    }

    /** Pools de posições por lado da moldura. */
    private function build_frame_pools( $rows, $cols, $cr, $cc ) {
        $left = $top = $right = $bottom = array();

        if ( $rows === 3 && $cols === 3 ) {
            for ( $c = 0; $c < 3; $c++ ) {
                $top[] = array( 0, $c );
            }
            $left[]  = array( 1, 0 );
            $right[] = array( 1, 2 );
            for ( $c = 0; $c < 3; $c++ ) {
                $bottom[] = array( 2, $c );
            }
            return compact( 'left', 'top', 'right', 'bottom' );
        }

        for ( $r = 1; $r < $rows - 1; $r++ ) {
            $left[]  = array( $r, 0 );
            $right[] = array( $r, $cols - 1 );
        }
        for ( $c = 1; $c < $cols - 1; $c++ ) {
            $top[]    = array( 0, $c );
            $bottom[] = array( $rows - 1, $c );
        }

        return compact( 'left', 'top', 'right', 'bottom' );
    }

    /** Distribui N itens na ordem aranha: esquerda → topo → direita → base. */
    private function distribute_frame_counts( $n, $max_l, $max_t, $max_r, $max_b ) {
        $left   = min( $max_l, $n );
        $n     -= $left;
        $top    = min( $max_t, $n );
        $n     -= $top;
        $right  = min( $max_r, $n );
        $n     -= $right;
        $bottom = min( $max_b, $n );
        $n     -= $bottom;

        if ( $n > 0 ) {
            $add = min( $max_b - $bottom, $n );
            $bottom += $add;
            $n     -= $add;
            $top = min( $max_t, $top + $n );
        }

        return array(
            'left'   => $left,
            'top'    => $top,
            'right'  => max( 0, $right ),
            'bottom' => max( 0, $bottom ),
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

    /** Resolve estilo de borda dos cards. */
    private function resolve_card_border( $settings ) {
        $allowed = array( 'none', 'solid', 'dashed', 'dotted', 'double' );
        $style   = isset( $settings['card_border_style'] ) ? sanitize_key( $settings['card_border_style'] ) : 'none';
        if ( ! in_array( $style, $allowed, true ) ) {
            $style = 'none';
        }

        $width = max( 0, min( 20, intval( isset( $settings['card_border_width'] ) ? $settings['card_border_width'] : 0 ) ) );
        $color = esc_attr( isset( $settings['card_border_color'] ) ? $settings['card_border_color'] : '#d0d0d0' );

        $css = ( 'none' === $style || $width <= 0 )
            ? 'border:none;'
            : 'border:' . $width . 'px ' . $style . ' ' . $color . ';';

        return array(
            'style' => $style,
            'width' => $width,
            'color' => $color,
            'css'   => $css,
        );
    }

    /** Mapeia alinhamento vertical do texto para valor flex. */
    private function card_align_flex( $align ) {
        $map = array(
            'top'    => 'flex-start',
            'center' => 'center',
            'bottom' => 'flex-end',
        );
        return isset( $map[ $align ] ) ? $map[ $align ] : 'flex-start';
    }

    /** Converte cor hex + opacidade (0–100) para rgba. */
    private function hex_to_rgba( $hex, $opacity_pct ) {
        $hex = ltrim( (string) $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
            return 'rgba(208,216,196,1)';
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        $a = max( 0, min( 100, intval( $opacity_pct ) ) ) / 100;
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
    }

    /** Gera box-shadow a partir de intensidade 0–100. */
    private function build_box_shadow( $intensity ) {
        $i = max( 0, min( 100, intval( $intensity ) ) );
        if ( $i <= 0 ) {
            return 'none';
        }
        $y     = max( 1, (int) round( $i * 0.08 ) );
        $blur  = max( 2, (int) round( $i * 0.32 ) );
        $alpha = round( $i * 0.0012, 3 );
        return '0 ' . $y . 'px ' . $blur . 'px rgba(0,0,0,' . $alpha . ')';
    }
}

// Mantido apenas como motor de render do elemento unificado "aranha".
// Vitrine_Element_Registry::register( new Vitrine_Element_Aranha3() );
