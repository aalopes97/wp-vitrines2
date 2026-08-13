<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Elemento Aranha unificado: circular (orbital) ou grade (moldura).
 * Delega o render para as classes históricas aranha2 / aranha3.
 */
class Vitrine_Element_Aranha extends Vitrine_Element {

    public function slug() {
        return 'aranha';
    }

    public function label() {
        return 'Aranha';
    }

    public function icon() {
        return 'dashicons-chart-pie';
    }

    public function defaults() {
        return array_merge(
            array(
                'layout_mode'          => 'circular',
                // Compartilhados
                'center_image'         => '',
                'center_size'          => '160',
                'center_bg_color'      => '#ffffff',
                'bg_color'             => '#f8f9fa',
                'card_bg'              => '#ffffff',
                'title_color'          => '#1d2327',
                'text_color'           => '#555555',
                'icon_size'            => '36',
                'icon_color'           => '#2e7d32',
                'card_style'           => 'default',
                'card_min_height'      => '190',
                'items'                => array(),
                // Circular
                'center_label'         => '',
                'radius'               => '200',
                'card_border'          => '#2e7d32',
                // Grade
                'center_image_fit'     => 'cover',
                'columns'              => '3',
                'border_radius'        => '12',
                'card_border_radius'   => '12',
                'card_border_style'    => 'none',
                'card_border_width'    => '1',
                'card_border_color'    => '#d0d0d0',
                'image_border_radius'  => '12',
                'card_shadow'          => '6',
                'center_bg_opacity'    => '100',
                'image_shadow'         => '0',
                'gap'                  => '16',
                'card_height'          => '140',
                'card_text_align'      => 'top',
                'wrapper_padding'      => '28',
                'wrapper_border_style' => 'none',
            ),
            self::card_preset_defaults()
        );
    }

    public function fields() {
        return array_merge(
            array(
                array(
                    'name'    => 'layout_mode',
                    'label'   => 'Formato',
                    'type'    => 'select',
                    'options' => array(
                        'circular' => 'Circular',
                        'grade'    => 'Grade',
                    ),
                ),
                array( 'name' => 'center_image',         'label' => 'Imagem Central',              'type' => 'image' ),
                array( 'name' => 'center_image_fit',     'label' => 'Ajuste da imagem central',    'type' => 'select', 'options' => array( 'cover' => 'Cover (preenche)', 'contain' => 'Contain (inteira)' ) ),
                array( 'name' => 'center_size',          'label' => 'Tamanho central (px)',        'type' => 'number' ),
                array( 'name' => 'center_label',         'label' => 'Rótulo central',              'type' => 'text' ),
                array( 'name' => 'center_bg_color',      'label' => 'Cor de fundo centro',         'type' => 'color' ),
                array( 'name' => 'center_bg_opacity',    'label' => 'Opacidade do fundo (%)',      'type' => 'range', 'min' => 0, 'max' => 100 ),
                array( 'name' => 'radius',               'label' => 'Raio orbital (px)',           'type' => 'number' ),
                array( 'name' => 'columns',              'label' => 'Colunas (referência)',        'type' => 'number' ),
                array( 'name' => 'icon_size',            'label' => 'Tam. ícones (px)',            'type' => 'number' ),
                array( 'name' => 'icon_color',           'label' => 'Cor dos ícones',              'type' => 'color' ),
                array(
                    'name'    => 'card_style',
                    'label'   => 'Modelo do card',
                    'type'    => 'select',
                    'options' => array(
                        'default'     => 'Padrão',
                        'dark'        => 'Escuro (ícone acima)',
                        'white'       => 'Branco (ícone ao lado)',
                        'border-left' => 'Borda esquerda',
                    ),
                ),
                array( 'name' => 'card_min_height',      'label' => 'Altura mínima dos cards (px)', 'type' => 'number' ),
                array( 'name' => 'card_height',          'label' => 'Altura dos cards (px)',       'type' => 'number' ),
                array( 'name' => 'card_bg',              'label' => 'Cor fundo card',              'type' => 'color' ),
                array( 'name' => 'card_border',          'label' => 'Cor destaque (bordas)',       'type' => 'color' ),
                array( 'name' => 'card_border_radius',   'label' => 'Arredondamento cards (px)',   'type' => 'number' ),
                array( 'name' => 'card_border_style',    'label' => 'Borda dos cards',             'type' => 'select', 'options' => array( 'none' => 'Nenhuma', 'solid' => 'Sólida', 'dashed' => 'Tracejada', 'dotted' => 'Pontilhada', 'double' => 'Dupla' ) ),
                array( 'name' => 'card_border_width',    'label' => 'Largura da borda (px)',       'type' => 'number' ),
                array( 'name' => 'card_border_color',    'label' => 'Cor da borda',                'type' => 'color' ),
                array( 'name' => 'image_border_radius',  'label' => 'Arredondamento imagem (px)',  'type' => 'number' ),
                array( 'name' => 'card_shadow',          'label' => 'Sombra dos cards',            'type' => 'range', 'min' => 0, 'max' => 100 ),
                array( 'name' => 'image_shadow',         'label' => 'Sombra da imagem',            'type' => 'range', 'min' => 0, 'max' => 100 ),
                array( 'name' => 'gap',                  'label' => 'Espaçamento (px)',            'type' => 'number' ),
                array( 'name' => 'card_text_align',      'label' => 'Alinhamento do texto',        'type' => 'select', 'options' => array( 'top' => 'Topo', 'center' => 'Meio', 'bottom' => 'Base' ) ),
                array( 'name' => 'title_color',          'label' => 'Cor do título',               'type' => 'color' ),
                array( 'name' => 'text_color',           'label' => 'Cor do texto',                'type' => 'color' ),
                array( 'name' => 'bg_color',             'label' => 'Cor de fundo',                'type' => 'color' ),
                array( 'name' => 'wrapper_padding',      'label' => 'Padding do bloco (px)',       'type' => 'number' ),
                array( 'name' => 'wrapper_border_style', 'label' => 'Borda do bloco',              'type' => 'select', 'options' => array( 'none' => 'Nenhuma', 'solid' => 'Sólida' ) ),
            ),
            self::card_preset_fields()
        );
    }

    /**
     * @param array  $settings
     * @return string circular|grade
     */
    public static function resolve_layout_mode( $settings ) {
        $mode = isset( $settings['layout_mode'] ) ? sanitize_key( $settings['layout_mode'] ) : 'circular';
        return ( 'grade' === $mode ) ? 'grade' : 'circular';
    }

    public function render( $settings, $children_html = '' ) {
        $s    = wp_parse_args( $settings, $this->defaults() );
        $mode = self::resolve_layout_mode( $s );

        // element-aranha.php carrega antes de aranha2/3 no glob; garante as classes.
        if ( ! class_exists( 'Vitrine_Element_Aranha2' ) ) {
            require_once VITRINE_PATH . 'elements/element-aranha2.php';
        }
        if ( ! class_exists( 'Vitrine_Element_Aranha3' ) ) {
            require_once VITRINE_PATH . 'elements/element-aranha3.php';
        }

        if ( 'grade' === $mode ) {
            $delegate = new Vitrine_Element_Aranha3();
            return $delegate->render( $s, $children_html );
        }

        $delegate = new Vitrine_Element_Aranha2();
        return $delegate->render( $s, $children_html );
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Aranha() );
