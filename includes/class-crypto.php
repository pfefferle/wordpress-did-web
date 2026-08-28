<?php
/**
 * Cryptographic functions for DID Web
 *
 * @package Did_Web
 */

namespace Did_Web;

// Exit if accessed directly.
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
	 * Multicodec prefix for a secp256k1 public key (`secp256k1-pub`).
	 *
	 * The varint encoding of code 0xe7 is the two bytes 0xe7 0x01. This is the
	 * prefix used by the AT Protocol Multikey / did:key representation.
	 */
	const MULTICODEC_SECP256K1_PUB = "\xe7\x01";

	/**
	 * The base58btc alphabet (Bitcoin ordering).
	 */
	const BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

	/**
	 * Generate a new keypair
	 *
	 * Generates a secp256k1 keypair suitable for AT Protocol using PHP's
	 * native OpenSSL extension. The public key can be converted to its
	 * Multikey multibase form via public_key_to_multibase().
	 *
	 * @return array|false Array with 'private' and 'public' keys, or false on failure.
	 */
	public static function generate_keypair() {
		// Check if OpenSSL is available.
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			return false;
		}

		// Generate a secp256k1 EC keypair (the curve AT Protocol uses).
		$config = array(
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name'       => 'secp256k1',
		);

		$private_key_resource = openssl_pkey_new( $config );

		if ( ! $private_key_resource ) {
			return false;
		}

		// Export private key.
		$private_key = '';
		openssl_pkey_export( $private_key_resource, $private_key );

		// Get public key.
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
		update_option( self::OPTION_PRIVATE_KEY, Encryption::encrypt( $private_key ), false );
		update_option( self::OPTION_PUBLIC_KEY, $public_key, false );

		// Verify the keys were saved by reading them back (decrypting the private key).
		return self::get_private_key() === $private_key
			&& get_option( self::OPTION_PUBLIC_KEY ) === $public_key;
	}

	/**
	 * Get stored private key
	 *
	 * The private key is stored encrypted at rest; this decrypts it for use.
	 *
	 * @return string|false Private key in PEM format, or false if not found or undecryptable.
	 */
	public static function get_private_key() {
		$stored = get_option( self::OPTION_PRIVATE_KEY, false );
		if ( false === $stored || '' === $stored ) {
			return false;
		}

		return Encryption::decrypt( $stored );
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

		$compressed = self::pem_to_compressed_public_key( $public_key );
		if ( false === $compressed ) {
			return false;
		}

		return self::compressed_public_key_to_multibase( $compressed );
	}

	/**
	 * Extract the 33-byte compressed point from a PEM-encoded secp256k1 public key.
	 *
	 * @param string $public_key Public key in PEM format.
	 * @return string|false Raw 33-byte compressed key, or false on failure.
	 */
	private static function pem_to_compressed_public_key( $public_key ) {
		if ( ! function_exists( 'openssl_pkey_get_public' ) || ! is_string( $public_key ) || '' === $public_key ) {
			return false;
		}

		$resource = openssl_pkey_get_public( $public_key );
		if ( false === $resource ) {
			return false;
		}

		$details = openssl_pkey_get_details( $resource );
		if ( ! isset( $details['ec']['x'], $details['ec']['y'] ) ) {
			return false;
		}

		// secp256k1 field elements are 32 bytes; left-pad in case of a stripped leading zero.
		$x = str_pad( $details['ec']['x'], 32, "\0", STR_PAD_LEFT );
		$y = $details['ec']['y'];

		// Point compression: 0x02 if Y is even, 0x03 if odd.
		$prefix = ( ord( $y[ strlen( $y ) - 1 ] ) & 1 ) ? "\x03" : "\x02";

		return $prefix . $x;
	}

	/**
	 * Encode a raw compressed secp256k1 public key as a Multikey multibase string.
	 *
	 * Prepends the secp256k1-pub multicodec prefix, base58btc-encodes the
	 * result, and prefixes the multibase identifier `z`. This is the form used
	 * in DID document `publicKeyMultibase` fields and did:key identifiers.
	 *
	 * @param string $compressed_key Raw 33-byte compressed public key (0x02|0x03 || X).
	 * @return string|false Multibase string starting with `z`, or false if input is invalid.
	 */
	public static function compressed_public_key_to_multibase( $compressed_key ) {
		if ( ! is_string( $compressed_key ) || 33 !== strlen( $compressed_key ) ) {
			return false;
		}

		$first = ord( $compressed_key[0] );
		if ( 0x02 !== $first && 0x03 !== $first ) {
			return false;
		}

		return 'z' . self::base58btc_encode( self::MULTICODEC_SECP256K1_PUB . $compressed_key );
	}

	/**
	 * Encode raw bytes as base58btc (Bitcoin alphabet, no multibase prefix).
	 *
	 * @param string $bytes Raw bytes to encode.
	 * @return string Base58btc-encoded string.
	 */
	private static function base58btc_encode( $bytes ) {
		$values = array_values( unpack( 'C*', $bytes ) );
		$length = count( $values );

		// Leading zero bytes map directly to leading '1' characters.
		$zeros = 0;
		while ( $zeros < $length && 0 === $values[ $zeros ] ) {
			++$zeros;
		}

		// Convert the big-endian byte string into base58 digits (little-endian).
		$digits = array();
		for ( $i = $zeros; $i < $length; $i++ ) {
			$carry = $values[ $i ];
			foreach ( $digits as $index => $digit ) {
				$carry            = ( $digit * 256 ) + $carry;
				$digits[ $index ] = $carry % 58;
				$carry            = intdiv( $carry, 58 );
			}
			while ( $carry > 0 ) {
				$digits[] = $carry % 58;
				$carry    = intdiv( $carry, 58 );
			}
		}

		$output = str_repeat( '1', $zeros );
		for ( $i = count( $digits ) - 1; $i >= 0; $i-- ) {
			$output .= self::BASE58_ALPHABET[ $digits[ $i ] ];
		}

		return $output;
	}

	/**
	 * Sign a payload with the stored private key using ES256K.
	 *
	 * Produces an ECDSA signature over secp256k1 with SHA-256, output as the
	 * raw R||S form (64 bytes), normalised to low-S (required by AT Protocol),
	 * and base64url-encoded.
	 *
	 * @param string $payload Raw bytes to sign.
	 * @return string|false Base64url-encoded signature, or false on failure.
	 */
	public static function sign( $payload ) {
		if ( ! function_exists( 'openssl_sign' ) ) {
			return false;
		}

		$pem = self::get_private_key();
		if ( false === $pem ) {
			return false;
		}

		$key = openssl_pkey_get_private( $pem );
		if ( false === $key ) {
			return false;
		}

		$der_signature = '';
		if ( ! openssl_sign( $payload, $der_signature, $key, OPENSSL_ALGO_SHA256 ) ) {
			return false;
		}

		$raw = self::der_to_raw( $der_signature, 64 );
		if ( false === $raw ) {
			return false;
		}

		// Normalise S to the low half of the curve order.
		$raw = substr( $raw, 0, 32 ) . self::normalize_s( substr( $raw, 32, 32 ) );

		return self::base64url( $raw );
	}

	/**
	 * Verify an ES256K signature against a PEM-encoded public key.
	 *
	 * @param string $payload    Raw bytes that were signed.
	 * @param string $signature  Base64url-encoded raw R||S signature.
	 * @param string $public_key Public key in PEM format.
	 * @return bool True if the signature is valid, false otherwise.
	 */
	public static function verify( $payload, $signature, $public_key ) {
		if ( ! function_exists( 'openssl_verify' ) ) {
			return false;
		}

		$raw = self::base64url_decode( $signature );
		if ( false === $raw || 64 !== strlen( $raw ) ) {
			return false;
		}

		$key = openssl_pkey_get_public( $public_key );
		if ( false === $key ) {
			return false;
		}

		$der = self::raw_to_der( $raw );

		return 1 === openssl_verify( $payload, $der, $key, OPENSSL_ALGO_SHA256 );
	}

	/**
	 * Normalise an ECDSA S value to the low half of the secp256k1 order.
	 *
	 * AT Protocol mandates low-S signatures: if S is greater than n/2 it is
	 * replaced with n - S. Implemented with pure byte arithmetic so it works
	 * without the GMP or BCMath extensions.
	 *
	 * @param string $s Raw 32-byte big-endian S value.
	 * @return string Normalised 32-byte big-endian S value.
	 */
	public static function normalize_s( $s ) {
		$s = str_pad( $s, 32, "\0", STR_PAD_LEFT );

		// secp256k1 group order (n) and its floor-halved value (n/2).
		$half  = hex2bin( '7fffffffffffffffffffffffffffffff5d576e7357a4501ddfe92f46681b20a0' );
		$order = hex2bin( 'fffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141' );

		// strcmp compares equal-length binary strings as unsigned big-endian.
		if ( strcmp( $s, $half ) <= 0 ) {
			return $s;
		}

		return self::subtract_256( $order, $s );
	}

	/**
	 * Subtract two 32-byte big-endian values (minuend >= subtrahend).
	 *
	 * @param string $a Minuend (32 bytes, big-endian).
	 * @param string $b Subtrahend (32 bytes, big-endian).
	 * @return string Difference (32 bytes, big-endian).
	 */
	private static function subtract_256( $a, $b ) {
		$result = '';
		$borrow = 0;

		for ( $i = 31; $i >= 0; $i-- ) {
			$diff = ord( $a[ $i ] ) - ord( $b[ $i ] ) - $borrow;
			if ( $diff < 0 ) {
				$diff  += 256;
				$borrow = 1;
			} else {
				$borrow = 0;
			}
			$result = chr( $diff ) . $result;
		}

		return $result;
	}

	/**
	 * Convert a DER-encoded ECDSA signature to raw R||S form.
	 *
	 * @param string $der    DER-encoded signature.
	 * @param int    $length Expected total length (64 for secp256k1).
	 * @return string|false Raw signature, or false on parse failure.
	 */
	private static function der_to_raw( $der, $length ) {
		$half = $length / 2;

		// secp256k1 signatures are always shorter than 128 bytes (short-form length).
		if ( ord( $der[0] ) !== 0x30 || ord( $der[1] ) & 0x80 ) {
			return false;
		}

		$offset = 2;

		// Parse R.
		if ( ord( $der[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$r_len   = ord( $der[ $offset + 1 ] );
		$r       = substr( $der, $offset + 2, $r_len );
		$offset += 2 + $r_len;

		// Parse S.
		if ( ord( $der[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$s_len = ord( $der[ $offset + 1 ] );
		$s     = substr( $der, $offset + 2, $s_len );

		// Strip leading zero padding, then left-pad to the field size.
		$r = str_pad( ltrim( $r, "\x00" ), $half, "\0", STR_PAD_LEFT );
		$s = str_pad( ltrim( $s, "\x00" ), $half, "\0", STR_PAD_LEFT );

		return $r . $s;
	}

	/**
	 * Convert a raw R||S signature to DER encoding.
	 *
	 * @param string $raw Raw 64-byte R||S signature.
	 * @return string DER-encoded signature.
	 */
	private static function raw_to_der( $raw ) {
		$r = self::asn1_integer( substr( $raw, 0, 32 ) );
		$s = self::asn1_integer( substr( $raw, 32, 32 ) );

		return "\x30" . self::asn1_length( $r . $s ) . $r . $s;
	}

	/**
	 * Encode a big-endian unsigned integer as an ASN.1 DER INTEGER.
	 *
	 * @param string $bytes Big-endian integer bytes.
	 * @return string DER-encoded INTEGER.
	 */
	private static function asn1_integer( $bytes ) {
		$bytes = ltrim( $bytes, "\x00" );
		if ( '' === $bytes ) {
			$bytes = "\x00";
		}

		// Prepend a zero byte if the high bit is set, to keep the value positive.
		if ( ord( $bytes[0] ) & 0x80 ) {
			$bytes = "\x00" . $bytes;
		}

		return "\x02" . self::asn1_length( $bytes ) . $bytes;
	}

	/**
	 * Encode an ASN.1 DER length prefix.
	 *
	 * @param string $content Content to measure.
	 * @return string DER-encoded length bytes.
	 */
	private static function asn1_length( $content ) {
		$length = strlen( $content );
		if ( $length < 0x80 ) {
			return chr( $length );
		}

		$length_bytes = ltrim( pack( 'N', $length ), "\x00" );
		return chr( 0x80 | strlen( $length_bytes ) ) . $length_bytes;
	}

	/**
	 * Base64url-encode without padding.
	 *
	 * @param string $data Raw bytes.
	 * @return string Base64url string.
	 */
	private static function base64url( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Base64url-decode.
	 *
	 * @param string $data Base64url string.
	 * @return string|false Decoded bytes, or false on failure.
	 */
	private static function base64url_decode( $data ) {
		if ( ! is_string( $data ) ) {
			return false;
		}

		return base64_decode( strtr( $data, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	/**
	 * Check if keypair exists
	 *
	 * @return bool True if both keys exist, false otherwise.
	 */
	public static function has_keypair() {
		return false !== self::get_private_key() && false !== self::get_public_key();
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
