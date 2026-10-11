<?php
/**
 * Test Crypto class.
 *
 * @package Did_Web
 */

namespace Did_Web\Tests;

use Did_Web\Crypto;

/**
 * Test class for Crypto.
 *
 * @coversDefaultClass \Did_Web\Crypto
 */
class Test_Crypto extends Did_Web_Testcase {

	/**
	 * Test has_keypair returns false when no keys.
	 *
	 * @covers ::has_keypair
	 */
	public function test_has_keypair_returns_false_when_no_keys() {
		$this->assertFalse( Crypto::has_keypair() );
	}

	/**
	 * Test save_keypair stores keys.
	 *
	 * @covers ::save_keypair
	 * @covers ::has_keypair
	 */
	public function test_save_keypair() {
		$result = Crypto::save_keypair( 'test_private_key', 'test_public_key' );

		$this->assertTrue( $result );
		$this->assertTrue( Crypto::has_keypair() );
	}

	/**
	 * Test get_private_key returns stored key.
	 *
	 * @covers ::get_private_key
	 */
	public function test_get_private_key() {
		Crypto::save_keypair( 'test_private_key', 'test_public_key' );

		$this->assertEquals( 'test_private_key', Crypto::get_private_key() );
	}

	/**
	 * Test the private key is stored encrypted, not in plaintext.
	 *
	 * @covers ::save_keypair
	 * @covers ::get_private_key
	 */
	public function test_private_key_is_encrypted_at_rest() {
		$pem = "-----BEGIN EC PRIVATE KEY-----\nsecret-bytes\n-----END EC PRIVATE KEY-----";

		Crypto::save_keypair( $pem, 'test_public_key' );

		$raw_option = get_option( Crypto::OPTION_PRIVATE_KEY );
		$this->assertNotSame( $pem, $raw_option, 'Raw option must not be the plaintext PEM.' );
		$this->assertStringNotContainsString( 'BEGIN EC PRIVATE KEY', $raw_option );

		// But the accessor still returns the original PEM.
		$this->assertSame( $pem, Crypto::get_private_key() );
	}

	/**
	 * Test the private key survives repeated reads (stable across page loads).
	 *
	 * @covers ::get_private_key
	 */
	public function test_private_key_round_trips_across_reads() {
		$pem = 'persistent-private-key';
		Crypto::save_keypair( $pem, 'pub' );

		$this->assertSame( $pem, Crypto::get_private_key() );
		$this->assertSame( $pem, Crypto::get_private_key() );
	}

	/**
	 * Test get_private_key returns false when nothing is stored.
	 *
	 * @covers ::get_private_key
	 */
	public function test_get_private_key_returns_false_when_absent() {
		$this->assertFalse( Crypto::get_private_key() );
	}

	/**
	 * Test get_public_key returns stored key.
	 *
	 * @covers ::get_public_key
	 */
	public function test_get_public_key() {
		Crypto::save_keypair( 'test_private_key', 'test_public_key' );

		$this->assertEquals( 'test_public_key', Crypto::get_public_key() );
	}

	/**
	 * Test delete_keypair removes keys.
	 *
	 * @covers ::delete_keypair
	 */
	public function test_delete_keypair() {
		Crypto::save_keypair( 'test_private_key', 'test_public_key' );
		$this->assertTrue( Crypto::has_keypair() );

		Crypto::delete_keypair();

		$this->assertFalse( Crypto::has_keypair() );
	}

	/**
	 * Test public_key_to_multibase returns manual key if set.
	 *
	 * @covers ::public_key_to_multibase
	 */
	public function test_public_key_to_multibase_returns_manual_key() {
		update_option( 'did_web_public_key_multibase', 'zManualKey123' );

		$result = Crypto::public_key_to_multibase( 'some_pem_key' );

		$this->assertEquals( 'zManualKey123', $result );
	}

	/**
	 * Test public_key_to_multibase returns false for an unparseable key.
	 *
	 * @covers ::public_key_to_multibase
	 */
	public function test_public_key_to_multibase_returns_false_for_invalid_pem() {
		$result = Crypto::public_key_to_multibase( 'some_pem_key' );

		$this->assertFalse( $result );
	}

	/**
	 * Canonical secp256k1 compressed-key -> Multikey multibase vectors.
	 *
	 * Sourced from Bluesky's atproto interop test suite
	 * (`crypto/signature-fixtures.json`, the ES256K entries). The Multikey /
	 * did:key form is base58btc( 0xe7 0x01 || 33-byte-compressed-pubkey )
	 * prefixed with 'z'. Decoded here with an independent base58 implementation.
	 *
	 * @return array<string, array{0: string, 1: string}> Hex compressed key, expected multibase.
	 */
	public function multikey_vector_provider() {
		return array(
			'atproto vector A' => array(
				'03a7d7fbf04846fa1fcff728ba594f3c5819345e88908e874b537ba5a65d1fc3bb',
				'zQ3shqwJEJyMBsBXCWyCBpUBMqxcon9oHB7mCvx4sSpMdLJwc',
			),
			'atproto vector B' => array(
				'037a188209f522b6fdde87247d92e74a7fef702e03011394945d5e72f57189e17c',
				'zQ3shnriYMXc8wvkbJqfNWh5GXn2bVAeqTC92YuNbek4npqGF',
			),
		);
	}

	/**
	 * Test the raw compressed-key -> Multikey multibase encoding against atproto vectors.
	 *
	 * @dataProvider multikey_vector_provider
	 * @covers ::compressed_public_key_to_multibase
	 *
	 * @param string $compressed_hex Hex of the 33-byte compressed secp256k1 public key.
	 * @param string $expected       Expected multibase (Multikey) string.
	 */
	public function test_compressed_public_key_to_multibase_matches_atproto_vectors( $compressed_hex, $expected ) {
		$compressed = hex2bin( $compressed_hex );

		$this->assertSame( $expected, Crypto::compressed_public_key_to_multibase( $compressed ) );
	}

	/**
	 * Test public_key_to_multibase on a real generated secp256k1 key.
	 *
	 * Verifies the PEM -> compressed-point -> multibase path produces a value
	 * that decodes back to the multicodec prefix (0xe7 0x01) plus a 33-byte
	 * compressed key whose leading byte marks parity (0x02 or 0x03).
	 *
	 * @covers ::public_key_to_multibase
	 */
	public function test_public_key_to_multibase_encodes_generated_key() {
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			$this->markTestSkipped( 'OpenSSL not available.' );
		}

		$keypair = Crypto::generate_keypair();
		if ( false === $keypair ) {
			$this->markTestSkipped( 'secp256k1 not supported by this OpenSSL build.' );
		}

		$multibase = Crypto::public_key_to_multibase( $keypair['public'] );

		$this->assertIsString( $multibase );
		$this->assertSame( 'z', $multibase[0], 'Multibase value must use the base58btc "z" prefix.' );

		// The compressed key path must agree with the raw-bytes encoder.
		$details    = openssl_pkey_get_details( openssl_pkey_get_public( $keypair['public'] ) );
		$x          = str_pad( $details['ec']['x'], 32, "\0", STR_PAD_LEFT );
		$y          = $details['ec']['y'];
		$prefix     = ( ord( $y[ strlen( $y ) - 1 ] ) & 1 ) ? "\x03" : "\x02";
		$compressed = $prefix . $x;

		$this->assertSame(
			Crypto::compressed_public_key_to_multibase( $compressed ),
			$multibase,
			'PEM path must match the raw compressed-key encoder.'
		);
	}

	/**
	 * The secp256k1 group order halved (low-S threshold), big-endian.
	 */
	const HALF_ORDER_HEX = '7fffffffffffffffffffffffffffffff5d576e7357a4501ddfe92f46681b20a0';

	/**
	 * Test sign returns false when no private key is stored.
	 *
	 * @covers ::sign
	 */
	public function test_sign_returns_false_without_private_key() {
		$this->assertFalse( Crypto::sign( 'payload' ) );
	}

	/**
	 * Test that a produced signature verifies against the public key, and a
	 * tampered payload does not.
	 *
	 * @covers ::sign
	 * @covers ::verify
	 */
	public function test_sign_and_verify_roundtrip() {
		$keypair = Crypto::generate_keypair();
		if ( false === $keypair ) {
			$this->markTestSkipped( 'secp256k1 not supported by this OpenSSL build.' );
		}
		Crypto::save_keypair( $keypair['private'], $keypair['public'] );

		$payload   = 'the-bytes-to-sign';
		$signature = Crypto::sign( $payload );

		$this->assertIsString( $signature );
		$this->assertTrue( Crypto::verify( $payload, $signature, $keypair['public'] ) );
		$this->assertFalse( Crypto::verify( 'tampered', $signature, $keypair['public'] ) );
	}

	/**
	 * Test that produced signatures are always low-S (required by AT Protocol).
	 *
	 * OpenSSL does not normalise S, so without explicit handling roughly half
	 * of all signatures would be high-S and rejected by atproto/PLC. Sign
	 * repeatedly to exercise the random component.
	 *
	 * @covers ::sign
	 */
	public function test_sign_always_produces_low_s() {
		$keypair = Crypto::generate_keypair();
		if ( false === $keypair ) {
			$this->markTestSkipped( 'secp256k1 not supported by this OpenSSL build.' );
		}
		Crypto::save_keypair( $keypair['private'], $keypair['public'] );

		$half = hex2bin( self::HALF_ORDER_HEX );

		for ( $i = 0; $i < 25; $i++ ) {
			$raw = base64_decode( strtr( Crypto::sign( "payload-$i" ), '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$s   = substr( $raw, 32, 32 );

			$this->assertLessThanOrEqual( 0, strcmp( $s, $half ), "Signature $i has a high-S value." );
		}
	}

	/**
	 * Test low-S normalisation against atproto's canonical fixtures.
	 *
	 * The atproto suite ships a matching pair: a valid low-S signature and the
	 * same signature with S replaced by (n - S), tagged "high-s" / invalid. A
	 * correct normaliser leaves the low value untouched and maps the high
	 * value back to the canonical low value.
	 *
	 * @covers ::normalize_s
	 */
	public function test_normalize_s_matches_atproto_vectors() {
		$low_s  = hex2bin( '27fc51339ce9ddb33fea82261bf3dcf0c5809b07bd5fef240ae4bafb35ab1fa0' );
		$high_s = hex2bin( 'd803aecc6316224cc0157dd9e40c230df52e41def1e8b117b4eda3919a8b21a1' );

		$this->assertSame( $low_s, Crypto::normalize_s( $low_s ), 'Already-low S must be unchanged.' );
		$this->assertSame( $low_s, Crypto::normalize_s( $high_s ), 'High S must normalise to the canonical low S.' );
	}

	/**
	 * Test generate_keypair returns false without OpenSSL.
	 *
	 * @covers ::generate_keypair
	 */
	public function test_generate_keypair() {
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			$this->assertFalse( Crypto::generate_keypair() );
			return;
		}

		$keypair = Crypto::generate_keypair();

		// May return false if secp256k1 not supported.
		if ( false === $keypair ) {
			$this->assertFalse( $keypair );
		} else {
			$this->assertIsArray( $keypair );
			$this->assertArrayHasKey( 'private', $keypair );
			$this->assertArrayHasKey( 'public', $keypair );
		}
	}
}
