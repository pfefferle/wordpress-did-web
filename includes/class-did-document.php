<?php
/**
 * DID Document generation
 *
 * @package Did_Web
 */

namespace Did_Web;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DID_Document class
 */
class DID_Document {

	/**
	 * Generate DID identifier from site URL
	 *
	 * @return string DID identifier (e.g., did:web:example.com)
	 */
	public static function get_did_identifier() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		// Handle port if not standard.
		$port = wp_parse_url( home_url(), PHP_URL_PORT );
		if ( $port && ! in_array( $port, array( 80, 443 ), true ) ) {
			$host .= '%3A' . $port; // URL-encode the colon.
		}

		// Handle path if site is in subdirectory.
		$path = wp_parse_url( home_url(), PHP_URL_PATH );
		if ( $path && '/' !== $path ) {
			$host .= ':' . trim( $path, '/' );
		}

		return sprintf( 'did:web:%s', $host );
	}

	/**
	 * Generate complete DID document
	 *
	 * @return array DID document as associative array
	 */
	public static function generate() {
		$did_identifier = self::get_did_identifier();

		// Get stored settings.
		$handle        = get_option( 'did_web_handle', '' );
		$pds_endpoint  = get_option( 'did_web_pds_endpoint', home_url() );
		$public_key_mb = get_option( 'did_web_public_key_multibase', '' );

		// If no public key is configured, try to get from crypto class.
		if ( empty( $public_key_mb ) && Crypto::has_keypair() ) {
			$public_key    = Crypto::get_public_key();
			$multibase     = Crypto::public_key_to_multibase( $public_key );
			$public_key_mb = $multibase ? $multibase : '';
		}

		$document = array(
			'@context' => array(
				'https://www.w3.org/ns/did/v1',
				'https://w3id.org/security/multikey/v1',
				'https://w3id.org/security/suites/secp256k1-2019/v1',
			),
			'id'       => $did_identifier,
		);

		// Add alsoKnownAs if handle is configured.
		if ( ! empty( $handle ) ) {
			$document['alsoKnownAs'] = array(
				'at://' . $handle,
			);
		}

		// Add verification method if public key exists.
		if ( ! empty( $public_key_mb ) ) {
			$document['verificationMethod'] = array(
				array(
					'id'                 => $did_identifier . '#atproto',
					'type'               => 'Multikey',
					'controller'         => $did_identifier,
					'publicKeyMultibase' => $public_key_mb,
				),
			);
		}

		// Add service endpoint for AT Protocol PDS.
		$document['service'] = array(
			array(
				'id'              => '#atproto_pds',
				'type'            => 'AtprotoPersonalDataServer',
				'serviceEndpoint' => $pds_endpoint,
			),
		);

		/**
		 * Filter the DID document before returning
		 *
		 * @param array $document DID document
		 */
		return apply_filters( 'did_web_document', $document );
	}

	/**
	 * Get DID document as JSON string
	 *
	 * @return string JSON-encoded DID document
	 */
	public static function get_json() {
		$document = self::generate();
		return wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
}
