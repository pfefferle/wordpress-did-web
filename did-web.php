<?php
/**
 * Plugin Name: DID Web
 * Plugin URI: https://github.com/pfefferle/wordpress-did
 * Description: Decentralized Identifier (DID) support for WordPress. Enables your WordPress site to serve DID documents at .well-known/did.json, with future Bluesky/ATProto compatibility.
 * Version: 1.0.0
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: did-web
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * DID: did:web:github.com:pfefferle:wordpress-did
 */

namespace Did_Web;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'DID_WEB_VERSION', '1.0.0' );
define( 'DID_WEB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DID_WEB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DID_WEB_PLUGIN_FILE', __FILE__ );

// Autoload classes
spl_autoload_register(
	function ( $class ) {
		// Only load our classes
		if ( strpos( $class, 'Did\\' ) !== 0 ) {
			return;
		}

		// Convert class name to file path
		$class = str_replace( 'Did\\', '', $class );
		$class = str_replace( '\\', '/', $class );
		$class = strtolower( str_replace( '_', '-', $class ) );

		// Build file path
		$file = DID_WEB_PLUGIN_DIR . 'includes/class-' . $class . '.php';

		// Handle admin classes
		if ( strpos( $class, 'admin/' ) === 0 ) {
			$class = str_replace( 'admin/', '', $class );
			$file  = DID_WEB_PLUGIN_DIR . 'includes/admin/class-' . $class . '.php';
		}

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Initialize admin settings
if ( is_admin() ) {
	Admin\Settings::init();
}

// Initialize plugin identity
Plugin_Identity::init();

/**
 * Add rewrite rule for .well-known/did.json
 */
function rewrite_rule() {
	\add_rewrite_rule( '.well-known/did.json', 'index.php?did', 'top' );
}
\add_action( 'init', __NAMESPACE__ . '\rewrite_rule' );

/**
 * Add custom query var for DID endpoint
 *
 * @param array $vars Existing query vars.
 * @return array Modified query vars.
 */
function query_vars( $vars ) {
	$vars[] = 'did';
	return $vars;
}
\add_filter( 'query_vars', __NAMESPACE__ . '\query_vars' );

/**
 * Flush rewrite rules on plugin activation
 */
function flush_rewrite_rules() {
	rewrite_rule();
	Plugin_Identity::add_rewrite_rules();
	\flush_rewrite_rules();
}
\register_activation_hook( __FILE__, __NAMESPACE__ . '\flush_rewrite_rules' );

/**
 * Handle DID request.
 *
 * @param \WP $wp WordPress environment object.
 */
function did( $wp ) {
	$query_vars = $wp->query_vars;

	if ( array_key_exists( 'did', $query_vars ) ) {
		\header( 'Content-Type: application/json' );

		// Use the new DID_Document class
		echo DID_Document::get_json();
		\exit;
	}
}
\add_action( 'parse_request', __NAMESPACE__ . '\did' );
