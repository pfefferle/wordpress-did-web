<?php
/**
 * Plugin's own DID identity management
 *
 * This class manages the plugin's own decentralized identity,
 * separate from the WordPress sites that use the plugin.
 *
 * @package Did_Web
 */

namespace Did_Web;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin_Identity class
 */
class Plugin_Identity {

	const PLUGIN_DID = 'did:web:github.com:pfefferle:wordpress-did';
	const OPTION_PLUGIN_PRIVATE_KEY = 'did_plugin_identity_private_key';
	const OPTION_PLUGIN_PUBLIC_KEY  = 'did_plugin_identity_public_key';
	const OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE = 'did_plugin_identity_public_key_multibase';

	/**
	 * Initialize the plugin identity
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_action( 'parse_request', array( __CLASS__, 'handle_did_request' ) );
	}

	/**
	 * Add rewrite rule for plugin's DID document
	 */
	public static function add_rewrite_rules() {
		// Virtual endpoint for the plugin's own DID document
		// Accessible at: /did-plugin/did.json
		add_rewrite_rule( 'did-plugin/did.json$', 'index.php?plugin_did=1', 'top' );
	}

	/**
	 * Add custom query var
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'plugin_did';
		return $vars;
	}

	/**
	 * Handle plugin DID document request
	 *
	 * @param \WP $wp WordPress environment.
	 */
	public static function handle_did_request( $wp ) {
		if ( ! isset( $wp->query_vars['plugin_did'] ) ) {
			return;
		}

		header( 'Content-Type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Cache-Control: max-age=3600' );

		echo self::get_did_document_json();
		exit;
	}

	/**
	 * Generate the plugin's own keypair
	 *
	 * Stores all keys securely in WordPress options (never in filesystem).
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function generate_identity() {
		$keypair = Crypto::generate_keypair();

		if ( ! $keypair ) {
			return false;
		}

		// Save plugin's own keys in WordPress options (separate from site keys)
		update_option( self::OPTION_PLUGIN_PRIVATE_KEY, $keypair['private'], false );
		update_option( self::OPTION_PLUGIN_PUBLIC_KEY, $keypair['public'], false );

		// Generate multibase representation
		$multibase = Crypto::public_key_to_multibase( $keypair['public'] );
		if ( $multibase ) {
			update_option( self::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE, $multibase, false );
		}

		return true;
	}

	/**
	 * Check if plugin has its own identity
	 *
	 * @return bool True if identity exists, false otherwise.
	 */
	public static function has_identity() {
		return get_option( self::OPTION_PLUGIN_PRIVATE_KEY, false ) !== false;
	}

	/**
	 * Get the plugin's DID document
	 *
	 * @return array DID document.
	 */
	public static function get_did_document() {
		$public_key_multibase = get_option( self::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE, 'zTODO_GENERATE_KEY' );

		$document = array(
			'@context'   => array(
				'https://www.w3.org/ns/did/v1',
				'https://w3id.org/security/multikey/v1',
				'https://w3id.org/security/suites/secp256k1-2019/v1',
			),
			'id'         => self::PLUGIN_DID,
			'alsoKnownAs' => array(
				'https://github.com/pfefferle/wordpress-did',
			),
			'controller' => self::PLUGIN_DID,
			'verificationMethod' => array(
				array(
					'id'                 => self::PLUGIN_DID . '#key-1',
					'type'               => 'Multikey',
					'controller'         => self::PLUGIN_DID,
					'publicKeyMultibase' => $public_key_multibase,
				),
			),
			'authentication' => array(
				self::PLUGIN_DID . '#key-1',
			),
			'assertionMethod' => array(
				self::PLUGIN_DID . '#key-1',
			),
			'service' => array(
				array(
					'id'              => '#wordpress-plugin',
					'type'            => 'WordPressPlugin',
					'serviceEndpoint' => 'https://github.com/pfefferle/wordpress-did',
				),
				array(
					'id'              => '#repository',
					'type'            => 'GitRepository',
					'serviceEndpoint' => 'https://github.com/pfefferle/wordpress-did',
				),
			),
		);

		return $document;
	}

	/**
	 * Get the DID document as JSON string
	 *
	 * @return string JSON-encoded DID document.
	 */
	public static function get_did_document_json() {
		$document = self::get_did_document();
		return wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Export plugin's public key for verification
	 *
	 * @return array|false Public key data or false if not available.
	 */
	public static function export_public_key() {
		if ( ! self::has_identity() ) {
			return false;
		}

		return array(
			'pem'       => get_option( self::OPTION_PLUGIN_PUBLIC_KEY ),
			'multibase' => get_option( self::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE ),
			'did'       => self::PLUGIN_DID,
		);
	}

	/**
	 * Delete plugin's identity
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete_identity() {
		delete_option( self::OPTION_PLUGIN_PRIVATE_KEY );
		delete_option( self::OPTION_PLUGIN_PUBLIC_KEY );
		delete_option( self::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE );

		return true;
	}
}
