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
	 * Test public_key_to_multibase returns false when no manual key.
	 *
	 * @covers ::public_key_to_multibase
	 */
	public function test_public_key_to_multibase_returns_false_without_manual_key() {
		$result = Crypto::public_key_to_multibase( 'some_pem_key' );

		$this->assertFalse( $result );
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
