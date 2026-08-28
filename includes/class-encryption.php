<?php
/**
 * Symmetric encryption at rest via libsodium.
 *
 * The private key is encrypted before it touches the database and decrypted
 * only when it is about to be used. Adapted from the Atmosphere plugin's
 * Encryption helper, kept PHP 7.4 compatible.
 *
 * @package Did_Web
 */

namespace Did_Web;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encryption helper using sodium secretbox.
 */
class Encryption {

	/**
	 * Derive a 32-byte key from arbitrary secret material.
	 *
	 * @param string $material Secret material to derive from.
	 * @return string 32-byte key.
	 */
	public static function derive_key( $material ) {
		return sodium_crypto_generichash(
			$material,
			'',
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}

	/**
	 * Resolve the encryption key from the site's auth secrets.
	 *
	 * Prefers `AUTH_KEY . AUTH_SALT` so previously encrypted values still
	 * decrypt; falls back to `wp_salt( 'auth' )` on sites that don't define
	 * those constants.
	 *
	 * @return string 32-byte key.
	 */
	private static function key() {
		if (
			defined( 'AUTH_KEY' )
			&& defined( 'AUTH_SALT' )
			&& '' !== AUTH_KEY
			&& '' !== AUTH_SALT
		) {
			$material = AUTH_KEY . AUTH_SALT;
		} else {
			$material = wp_salt( 'auth' );
		}

		return self::derive_key( $material );
	}

	/**
	 * Encrypt a plaintext value.
	 *
	 * @param string $plaintext Value to protect.
	 * @return string Base64-encoded nonce + ciphertext.
	 */
	public static function encrypt( $plaintext ) {
		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, self::key() );

		return base64_encode( $nonce . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a previously encrypted value.
	 *
	 * @param string $encoded Base64 blob produced by encrypt().
	 * @return string|false Plaintext, or false on failure.
	 */
	public static function decrypt( $encoded ) {
		if ( ! is_string( $encoded ) || '' === $encoded ) {
			return false;
		}

		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $raw ) {
			return false;
		}

		$nonce_len = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

		if ( strlen( $raw ) < $nonce_len + 1 ) {
			return false;
		}

		return sodium_crypto_secretbox_open(
			substr( $raw, $nonce_len ),
			substr( $raw, 0, $nonce_len ),
			self::key()
		);
	}
}
