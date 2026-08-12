<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Plugin {

    private static $instance = null;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        Vitrine_I18n::init();
        Vitrine_Polylang::init();
        if ( is_admin() ) {
            Vitrine_Translations_Admin::init();
            Vitrine_AI::init();
        }

        add_action( 'init', array( $this, 'register_post_type' ) );
        add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg' ), 10, 2 );
        add_filter( 'single_template', array( $this, 'load_single_template' ) );

        if ( is_admin() ) {
            new Vitrine_Editor();
            new Vitrine_Hero_Meta();
        }

        new Vitrine_Render();
    }

    /**
     * Registra o CPT "vitrine".
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Vitrines', 'builder-vitrine' ),
            'singular_name'      => __( 'Vitrine', 'builder-vitrine' ),
            'add_new'            => __( 'Add New', 'builder-vitrine' ),
            'add_new_item'       => __( 'Add New Vitrine', 'builder-vitrine' ),
            'edit_item'          => __( 'Edit Vitrine', 'builder-vitrine' ),
            'new_item'           => __( 'New Vitrine', 'builder-vitrine' ),
            'view_item'          => __( 'View Vitrine', 'builder-vitrine' ),
            'search_items'       => __( 'Search Vitrines', 'builder-vitrine' ),
            'not_found'          => __( 'No vitrines found', 'builder-vitrine' ),
            'not_found_in_trash' => __( 'No vitrines in trash', 'builder-vitrine' ),
            'menu_name'          => __( 'Vitrines', 'builder-vitrine' ),
        );

        register_post_type( 'vitrine', array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'show_in_rest'        => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'revisions' ),
            'menu_icon'           => 'dashicons-layout',
            'rewrite'             => array( 'slug' => 'vitrine' ),
        ) );
    }

    /**
     * Desabilita Gutenberg para o post type vitrine.
     */
    public function disable_gutenberg( $use, $post_type ) {
        if ( 'vitrine' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Carrega o template single-vitrine.php do plugin.
     * O tema pode sobrescrever colocando single-vitrine.php na raiz do tema.
     */
    public function load_single_template( $template ) {
        if ( get_post_type() !== 'vitrine' ) {
            return $template;
        }

        // Permite que o tema sobrescreva
        $theme_file = locate_template( 'single-vitrine.php' );
        if ( $theme_file ) {
            return $theme_file;
        }

        $plugin_file = VITRINE_PATH . 'templates/single-vitrine.php';
        if ( file_exists( $plugin_file ) ) {
            return $plugin_file;
        }

        return $template;
    }

    /**
     * Carrega todos os elementos disponíveis na pasta /elements/.
     *
     * @return array Mapa slug => instância do elemento.
     */
    public static function load_elements() {
        static $elements = null;
        if ( null !== $elements ) {
            return $elements;
        }

        $elements = array();
        $dir      = VITRINE_PATH . 'elements/';

        if ( ! is_dir( $dir ) ) {
            return $elements;
        }

        require_once $dir . 'class-element.php';

        foreach ( glob( $dir . 'element-*.php' ) as $file ) {
            require_once $file;
        }

        // Cada arquivo de elemento registra-se via Vitrine_Element_Registry
        $elements = Vitrine_Element_Registry::get_all();
        return $elements;
    }
}
