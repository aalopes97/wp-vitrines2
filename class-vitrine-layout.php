<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitização e normalização do layout da vitrine.
 */
class Vitrine_Layout {

    /**
     * Sanitiza o array do layout de forma recursiva.
     *
     * @param array $layout Layout bruto.
     * @return array
     */
    public static function sanitize( $layout ) {
        if ( ! is_array( $layout ) ) {
            return array();
        }

        $clean = array();
        foreach ( $layout as $item ) {
            if ( ! is_array( $item ) || empty( $item['type'] ) ) {
                continue;
            }

            $item = self::migrate_aranha_item( $item );

            $clean_item = array(
                'type' => sanitize_key( $item['type'] ),
                'id'   => isset( $item['id'] ) ? sanitize_key( $item['id'] ) : self::generate_id(),
            );

            if ( isset( $item['settings'] ) && is_array( $item['settings'] ) ) {
                $clean_item['settings'] = self::sanitize_settings( $item['settings'], $clean_item['type'], $item['settings'] );
            } else {
                $clean_item['settings'] = array();
            }
            if ( 'aranha' === $clean_item['type'] ) {
                $clean_item['settings'] = self::normalize_aranha_settings( $clean_item['settings'] );
            }

            if ( isset( $item['height'] ) ) {
                $clean_item['height'] = absint( $item['height'] );
            }

            if ( isset( $item['width'] ) && is_string( $item['width'] ) ) {
                $clean_item['width'] = sanitize_text_field( $item['width'] );
            }

            if ( isset( $item['children'] ) && is_array( $item['children'] ) ) {
                $clean_item['children'] = self::sanitize( $item['children'] );
            }

            $clean[] = $clean_item;
        }

        return $clean;
    }

    /**
     * Unifica aranha2/aranha3 no elemento "aranha" com layout_mode.
     *
     * @param array $item
     * @return array
     */
    public static function migrate_aranha_item( $item ) {
        if ( ! is_array( $item ) || empty( $item['type'] ) ) {
            return $item;
        }

        $type = sanitize_key( $item['type'] );
        if ( ! isset( $item['settings'] ) || ! is_array( $item['settings'] ) ) {
            $item['settings'] = array();
        }

        if ( 'aranha2' === $type ) {
            $item['type'] = 'aranha';
            $item['settings']['layout_mode'] = 'circular';
        } elseif ( 'aranha3' === $type ) {
            $item['type'] = 'aranha';
            $item['settings']['layout_mode'] = 'grade';
        } elseif ( 'aranha' === $type ) {
            $mode = isset( $item['settings']['layout_mode'] ) ? sanitize_key( $item['settings']['layout_mode'] ) : 'circular';
            $item['settings']['layout_mode'] = ( 'grade' === $mode ) ? 'grade' : 'circular';
        }

        if ( 'aranha' === $item['type'] ) {
            $item['settings'] = self::normalize_aranha_settings( $item['settings'] );
        }

        if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
            foreach ( $item['children'] as $i => $child ) {
                $item['children'][ $i ] = self::migrate_aranha_item( $child );
            }
        }

        return $item;
    }

    /**
     * Migra layout completo (recursivo via migrate_aranha_item).
     *
     * @param mixed $layout
     * @return array
     */
    public static function migrate_aranha_layout( $layout ) {
        if ( ! is_array( $layout ) ) {
            return array();
        }
        $out = array();
        foreach ( $layout as $item ) {
            $out[] = self::migrate_aranha_item( $item );
        }
        return $out;
    }

    /**
     * Normaliza o contrato dos itens da Aranha, preservando rich text apenas
     * na descrição e atribuindo um ID estável para itens legados.
     *
     * @param array $settings Configurações da Aranha.
     * @return array
     */
    public static function normalize_aranha_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $items = isset( $settings['items'] ) && is_array( $settings['items'] )
            ? $settings['items']
            : array();
        $normalized_items = array();

        foreach ( $items as $index => $raw_item ) {
            if ( ! is_array( $raw_item ) ) {
                continue;
            }

            $title = isset( $raw_item['title'] )
                ? sanitize_text_field( wp_strip_all_tags( (string) $raw_item['title'] ) )
                : '';
            $text = isset( $raw_item['text'] )
                ? wp_kses_post( (string) $raw_item['text'] )
                : '';
            $icon = isset( $raw_item['icon'] )
                ? sanitize_text_field( (string) $raw_item['icon'] )
                : '';
            $link = isset( $raw_item['link'] )
                ? esc_url_raw( (string) $raw_item['link'] )
                : '';
            $alt = isset( $raw_item['alt'] )
                ? sanitize_text_field( (string) $raw_item['alt'] )
                : '';
            $id = isset( $raw_item['id'] ) ? sanitize_key( $raw_item['id'] ) : '';

            if ( ! $id ) {
                $id = 'a2_' . substr( md5( $index . '|' . $title . '|' . $icon . '|' . $link ), 0, 12 );
            }

            $normalized_item = array(
                'id'    => $id,
                'title' => $title,
                'text'  => $text,
                'icon'  => $icon,
                'link'  => $link,
                'alt'   => $alt,
            );
            if ( isset( $raw_item['position'] ) ) {
                $position = sanitize_key( $raw_item['position'] );
                $normalized_item['position'] = in_array( $position, array( 'auto', 'top', 'bottom', 'left', 'right' ), true )
                    ? $position
                    : 'auto';
            }
            $normalized_items[] = $normalized_item;
        }

        $settings['layout_mode'] = isset( $settings['layout_mode'] ) && 'grade' === sanitize_key( $settings['layout_mode'] )
            ? 'grade'
            : 'circular';
        $settings['items'] = $normalized_items;

        return $settings;
    }

    /**
     * Normaliza layout gerado (IDs, defaults, containers na raiz).
     *
     * @param array $layout Layout bruto.
     * @return array
     */
    public static function normalize( $layout ) {
        if ( ! is_array( $layout ) ) {
            return array();
        }

        Vitrine_Plugin::load_elements();

        $result = array();
        foreach ( $layout as $item ) {
            $normalized = self::normalize_item( $item );
            if ( ! $normalized ) {
                continue;
            }
            if ( 'container' !== $normalized['type'] ) {
                $normalized = self::wrap_in_container( $normalized );
            }
            $result[] = $normalized;
        }

        return self::sanitize( $result );
    }

    /**
     * Gera ID único para elemento do canvas.
     *
     * @return string
     */
    public static function generate_id() {
        return 'el_' . wp_generate_password( 8, false, false ) . '_' . (string) time();
    }

    /**
     * @param array $item Item bruto.
     * @return array|null
     */
    private static function normalize_item( $item ) {
        if ( ! is_array( $item ) || empty( $item['type'] ) ) {
            return null;
        }

        $item     = self::migrate_aranha_item( $item );
        $type     = sanitize_key( $item['type'] );
        $elements = Vitrine_Plugin::load_elements();
        if ( ! isset( $elements[ $type ] ) ) {
            return null;
        }

        $defaults = $elements[ $type ]->defaults();
        $settings = ( isset( $item['settings'] ) && is_array( $item['settings'] ) )
            ? wp_parse_args( $item['settings'], $defaults )
            : $defaults;

        $normalized = array(
            'type'     => $type,
            'id'       => ! empty( $item['id'] ) ? sanitize_key( $item['id'] ) : self::generate_id(),
            'settings' => $settings,
        );

        if ( isset( $item['height'] ) ) {
            $normalized['height'] = absint( $item['height'] );
        }

        if ( isset( $item['width'] ) && is_string( $item['width'] ) ) {
            $normalized['width'] = sanitize_text_field( $item['width'] );
        }

        if ( 'container' === $type && isset( $item['children'] ) && is_array( $item['children'] ) ) {
            $children = array();
            foreach ( $item['children'] as $child ) {
                $normalized_child = self::normalize_item( $child );
                if ( $normalized_child ) {
                    $children[] = $normalized_child;
                }
            }
            $normalized['children'] = $children;
        }

        return $normalized;
    }

    /**
     * @param array $child Elemento filho.
     * @return array
     */
    private static function wrap_in_container( $child ) {
        return array(
            'type'     => 'container',
            'id'       => self::generate_id(),
            'settings' => array(
                'name'      => '',
                'bg_color'  => '#ffffff',
                'direction' => 'column',
                'padding'   => '24',
                'gap'       => '16',
            ),
            'children' => array( $child ),
        );
    }

    /**
     * @param array  $settings Settings brutos.
     * @param string $type       Slug do elemento.
     * @param array  $raw        Settings originais (para HTML).
     * @return array
     */
    private static function sanitize_settings( $settings, $type, $raw ) {
        $clean = array_map(
            function ( $v ) {
                if ( is_array( $v ) ) {
                    return array_map(
                        function ( $sub ) {
                            if ( is_array( $sub ) ) {
                                return array_map(
                                    function ( $val ) {
                                        return wp_kses_post( (string) $val );
                                    },
                                    $sub
                                );
                            }
                            return sanitize_text_field( (string) $sub );
                        },
                        $v
                    );
                }
                if ( is_string( $v ) ) {
                    return wp_kses_post( $v );
                }
                if ( is_numeric( $v ) ) {
                    return $v;
                }
                return sanitize_text_field( (string) $v );
            },
            $settings
        );

        if ( 'html' === $type && isset( $raw['content'] ) ) {
            Vitrine_Plugin::load_elements();
            $clean['content'] = Vitrine_Element_Html::sanitize_html_content( $raw['content'] );
        }

        return $clean;
    }
}
