<?php
/**
 * Cryptographic functions for DID Web
 *
 * @package Did_Web
 */

namespace Did_Web;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Crypto class
 */
class Crypto {
	const OPTION_PRIVATE_KEY = 'did_web_private_key';
	const OPTION_PUBLIC_KEY  = 'did_web_public_key';

	/**
	 * Generate a new keypair
	 *
	 * This generates a secp256k1 keypair suitable for AT Protocol.
	 * Note: Requires additional PHP extension or library for production use.
	 *
	 * @return array|false Array with 'private' and 'public' keys, or false on failure.
	 */
	public static function generate_keypair() {
		// Check if OpenSSL is available
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			return false;
		}

		// For now, we'll use a placeholder that allows manual key import
		// In production, this should use secp256k1 library
		// Example libraries: simplito/elliptic-php or kornrunner/secp256k1

		// Generate EC keypair (as placeholder - should be secp256k1 for ATProto)
		$config = array(
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name'       => 'secp256k1',
		);

		$private_key_resource = openssl_pkey_new( $config );

		if ( ! $private_key_resource ) {
			return false;
		}

		// Export private key
		$private_key = '';
		openssl_pkey_export( $private_key_resource, $private_key );

		// Get public key
		$key_details = openssl_pkey_get_details( $private_key_resource );
		$public_key  = $key_details['key'];

		return array(
			'private' => $private_key,
			'public'  => $public_key,
		);
	}

	/**
	 * Save keypair to WordPress options
	 *
	 * @param string $private_key Private key in PEM format.
	 * @param string $public_key Public key in PEM format.
	 * @return bool True on success, false on failure.
	 */
	public static function save_keypair( $private_key, $public_key ) {
		update_option( self::OPTION_PRIVATE_KEY, $private_key, false );
		update_option( self::OPTION_PUBLIC_KEY, $public_key, false );

		// Verify the keys were saved by reading them back.
		return get_option( self::OPTION_PRIVATE_KEY ) === $private_key
			&& get_option( self::OPTION_PUBLIC_KEY ) === $public_key;
	}

	/**
	 * Get stored private key
	 *
	 * @return string|false Private key or false if not found.
	 */
	public static function get_private_key() {
		return get_option( self::OPTION_PRIVATE_KEY, false );
	}

	/**
	 * Get stored public key
	 *
	 * @return string|false Public key or false if not found.
	 */
	public static function get_public_key() {
		return get_option( self::OPTION_PUBLIC_KEY, false );
	}

	/**
	 * Convert public key to Multibase format
	 *
	 * Converts the public key to the Multibase format required by DID documents.
	 * Note: Full multibase encoding requires additional libraries (e.g., simplito/elliptic-php).
	 *
	 * @param string $public_key Public key in PEM format.
	 * @return string|false Public key in multibase format, or false if not configured.
	 */
	public static function public_key_to_multibase( $public_key ) {
		// Check for manually configured multibase key first.
		$manual_key = get_option( 'did_web_public_key_multibase', '' );
		if ( ! empty( $manual_key ) ) {
			return $manual_key;
		}

		/*
		 * Full multibase encoding requires:
		 * 1. Parse the PEM key
		 * 2. Extract the raw public key bytes
		 * 3. Add multicodec prefix for secp256k1-pub (0xe7)
		 * 4. Encode in base58btc with 'z' prefix
		 *
		 * This requires external libraries not bundled with this plugin.
		 * Users should generate keys externally and enter the multibase value in settings.
		 */
		return false;
	}

	/**
	 * Check if keypair exists
	 *
	 * @return bool True if both keys exist, false otherwise.
	 */
	public static function has_keypair() {
		return self::get_private_key() !== false && self::get_public_key() !== false;
	}

	/**
	 * Delete stored keypair
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete_keypair() {
		$private_deleted = delete_option( self::OPTION_PRIVATE_KEY );
		$public_deleted  = delete_option( self::OPTION_PUBLIC_KEY );
		delete_option( 'did_web_public_key_multibase' );

		return $private_deleted && $public_deleted;
	}
}
