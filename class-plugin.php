<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vitrine_Plugin {

    /**
     * Pasta dos manuais no GitHub (espelho dos PDFs de docs/).
     */
    const DOCS_URL = 'https://github.com/aalopes97/wp-vitrines2/tree/main/docs';

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
        add_action( 'admin_menu', array( $this, 'register_docs_menu' ), 20 );
        add_action( 'admin_footer', array( $this, 'open_docs_menu_in_new_tab' ) );

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
     * Submenu Vitrines → Manuais, apontando para a pasta docs no GitHub.
     */
    public function register_docs_menu() {
        global $submenu;

        $parent = 'edit.php?post_type=vitrine';
        if ( ! isset( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
            return;
        }

        $labels = array(
            'en' => 'Manuals',
            'es' => 'Manuales',
            'pt' => 'Manuais',
        );
        $lang    = strtolower( substr( (string) Vitrine_I18n::get_admin_language(), 0, 2 ) );
        $default = isset( $labels[ $lang ] ) ? $labels[ $lang ] : 'Manuais';

        $submenu[ $parent ][] = array(
            Vitrine_I18n::t( $default, 'ui.docs_menu' ),
            'edit_posts',
            self::DOCS_URL,
        );
    }

    /**
     * Abre o item Manuais em uma nova aba.
     */
    public function open_docs_menu_in_new_tab() {
        $url = esc_url( self::DOCS_URL );
        echo '<script>document.querySelectorAll("#adminmenu a[href=\'' . $url . '\']").forEach(function(link){link.target="_blank";link.rel="noopener noreferrer";});</script>';
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
