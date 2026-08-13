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
