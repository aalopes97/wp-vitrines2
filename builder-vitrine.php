<?php
/**
 * Plugin Name:  Builder Vitrine
 * Description:  Editor visual de vitrines para WooCommerce/WordPress.
<<<<<<< HEAD
 * Version:      1.3.11
 * Author:       Antonio
 * Author URI:   https://tonilopes.com.br
 * Plugin URI:   https://tonilopes.com.br
=======
 * Version:      1.3.16
 * Author:       Toni Lopes
>>>>>>> df00f61dbf5f0db1ef0abf43d469da8196abce2f
 * Text Domain:  builder-vitrine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VITRINE_VERSION', '1.3.16' );
define( 'VITRINE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'VITRINE_URL',     plugin_dir_url( __FILE__ ) );

require_once VITRINE_PATH . 'class-plugin.php';
require_once VITRINE_PATH . 'class-vitrine-i18n.php';
require_once VITRINE_PATH . 'class-vitrine-polylang.php';
require_once VITRINE_PATH . 'class-vitrine-translations-admin.php';
require_once VITRINE_PATH . 'class-vitrine-icons.php';
require_once VITRINE_PATH . 'class-vitrine-hero.php';
require_once VITRINE_PATH . 'class-vitrine-layout.php';
require_once VITRINE_PATH . 'class-vitrine-ai.php';
require_once VITRINE_PATH . 'class-editor.php';
require_once VITRINE_PATH . 'class-render.php';

add_action( 'plugins_loaded', array( 'Vitrine_Plugin', 'init' ) );
