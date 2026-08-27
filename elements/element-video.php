<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Element_Video extends Vitrine_Element {

    public function slug() {
        return 'video';
    }

    public function label() {
        return 'Vídeo';
    }

    public function icon() {
        return 'dashicons-video-alt3';
    }

    public function defaults() {
        return array(
            'qty'      => '1',
            'source_1' => 'youtube',
            'url_1'    => '',
            'width_1'  => '100',
            'source_2' => 'youtube',
            'url_2'    => '',
            'width_2'  => '50',
            'source_3' => 'youtube',
            'url_3'    => '',
            'width_3'  => '34',
            'aspect'   => '16-9',
            'autoplay' => '0',
            'controls' => '1',
        );
    }

    public function fields() {
        $fields = array(
            array(
                'name'    => 'qty',
                'label'   => 'Quantidade de vídeos',
                'type'    => 'select',
                'options' => array(
                    '1' => '1 vídeo',
                    '2' => '2 vídeos lado a lado',
                    '3' => '3 vídeos lado a lado',
                ),
            ),
        );

        for ( $i = 1; $i <= 3; $i++ ) {
            $fields[] = array(
                'name'    => "source_{$i}",
                'label'   => "Vídeo {$i} — origem",
                'type'    => 'select',
                'options' => array(
                    'youtube' => 'YouTube',
                    'local'   => 'Arquivo / biblioteca',
                ),
            );
            $fields[] = array(
                'name'  => "url_{$i}",
                'label' => "Vídeo {$i} — URL",
                'type'  => 'video',
            );
        }

        $fields[] = array( 'name' => 'aspect',   'label' => 'Proporção',            'type' => 'select', 'options' => array( '16-9' => '16:9', '4-3' => '4:3', '1-1' => '1:1' ) );
        $fields[] = array( 'name' => 'autoplay', 'label' => 'Autoplay',             'type' => 'select', 'options' => array( '0' => 'Não', '1' => 'Sim' ) );
        $fields[] = array( 'name' => 'controls', 'label' => 'Controles',            'type' => 'select', 'options' => array( '1' => 'Sim', '0' => 'Não' ) );
        $fields[] = array( 'name' => 'width_2',  'label' => 'Largura do vídeo 2 (%)', 'type' => 'number' );
        $fields[] = array( 'name' => 'width_3',  'label' => 'Largura do vídeo 3 (%)', 'type' => 'number' );

        return $fields;
    }

    public function render( $settings, $children_html = '' ) {
        $s = wp_parse_args( $settings, $this->defaults() );

        $qty      = max( 1, min( 3, intval( $s['qty'] ) ) );
        $autoplay = '1' === $s['autoplay'];
        $controls = '1' === $s['controls'];

        $aspects = array( '16-9' => '56.25', '4-3' => '75', '1-1' => '100' );
        $padding = isset( $aspects[ $s['aspect'] ] ) ? $aspects[ $s['aspect'] ] : '56.25';

        $gap = $qty > 1 ? 16 : 0;
        $default_widths = array( 1 => 100, 2 => 50, 3 => 34 );
        $video_widths   = array();
        $total_width    = 0;
        for ( $i = 1; $i <= $qty; $i++ ) {
            $configured_width = isset( $s[ "width_{$i}" ] ) ? intval( $s[ "width_{$i}" ] ) : 0;
            // width_1=100 é o padrão do modo de 1 vídeo; em 2/3 vídeos,
            // usa-se a divisão correspondente para evitar overflow horizontal.
            if ( $i === 1 && $qty > 1 && 100 === $configured_width ) {
                $configured_width = $default_widths[ $qty ];
            }
            if ( 2 === $i && 3 === $qty && 50 === $configured_width ) {
                $configured_width = $default_widths[ $qty ];
            }
            if ( $configured_width <= 0 ) {
                $configured_width = $default_widths[ $qty ];
            }
            $video_widths[ $i ] = max( 10, min( 90, $configured_width ) );
            $total_width       += $video_widths[ $i ];
        }

        $output = '<div class="vitrine-el-video-group" style="display:flex;flex-wrap:wrap;width:100%;max-width:100%;gap:' . $gap . 'px;align-items:flex-start;box-sizing:border-box;">';

        for ( $i = 1; $i <= $qty; $i++ ) {
            $source = isset( $s[ "source_{$i}" ] ) ? $s[ "source_{$i}" ] : 'youtube';
            $url    = isset( $s[ "url_{$i}" ] )    ? $s[ "url_{$i}" ]    : '';
            $width  = $video_widths[ $i ];

            // Retrocompatibilidade: slot 1 pode ter campos antigos
            if ( 1 === $i && empty( $url ) ) {
                if ( ! empty( $s['youtube_url'] ) ) {
                    $source = 'youtube';
                    $url    = $s['youtube_url'];
                } elseif ( ! empty( $s['local_url'] ) ) {
                    $source = 'local';
                    $url    = $s['local_url'];
                }
            }

            if ( $qty > 1 ) {
                $share     = number_format( ( $width / $total_width ) * 100, 4, '.', '' );
                $gap_share = number_format( $gap * ( $width / $total_width ), 4, '.', '' );
                $basis     = "calc({$share}% - {$gap_share}px)";
                $flex_style = "flex:0 1 {$basis};width:{$basis};max-width:{$basis};min-width:0;";
            } else {
                $flex_style = 'flex:1 1 100%;width:100%;max-width:100%;min-width:0;';
            }

            $output .= '<div class="vitrine-el-video" style="' . $flex_style . '">';
            $output .= '<div class="vitrine-el-video__wrapper" style="position:relative;padding-bottom:' . $padding . '%;height:0;overflow:hidden;">';

            if ( 'youtube' === $source && ! empty( $url ) ) {
                $video_id = $this->extract_youtube_id( $url );
                if ( $video_id ) {
                    $params = array( 'rel=0' );
                    if ( $autoplay ) { $params[] = 'autoplay=1'; $params[] = 'mute=1'; }
                    if ( ! $controls ) { $params[] = 'controls=0'; }
                    $src = 'https://www.youtube.com/embed/' . $video_id . '?' . implode( '&', $params );
                    $output .= '<iframe src="' . esc_url( $src ) . '" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                } else {
                    $output .= '<p style="text-align:center;color:#999;padding:20px;">URL do YouTube inválida.</p>';
                }
            } elseif ( 'local' === $source && ! empty( $url ) ) {
                $attrs = 'playsinline';
                if ( $controls ) { $attrs .= ' controls'; }
                if ( $autoplay ) { $attrs .= ' autoplay muted'; }
                $output .= '<video src="' . esc_url( $url ) . '" ' . $attrs . ' style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;"></video>';
            } else {
                $output .= '<p style="text-align:center;color:#999;padding:20px;">Nenhum vídeo selecionado.</p>';
            }

            $output .= '</div></div>';
        }

        $output .= '</div>';
        return $output;
    }

    private function extract_youtube_id( $url ) {
        $patterns = array(
            '/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/',
        );
        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }
        // Caso seja só o ID direto
        if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $url ) ) {
            return $url;
        }
        return '';
    }
}

Vitrine_Element_Registry::register( new Vitrine_Element_Video() );
