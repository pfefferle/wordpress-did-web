<?php
/**
 * Test Encryption class.
 *
 * @package Did_Web
 */

namespace Did_Web\Tests;

use Did_Web\Encryption;

/**
 * Test class for Encryption.
 *
 * @coversDefaultClass \Did_Web\Encryption
 */
class Test_Encryption extends Did_Web_Testcase {

	/**
	 * Test that encrypt -> decrypt round-trips the original value.
	 *
	 * @covers ::encrypt
	 * @covers ::decrypt
	 */
	public function test_encrypt_decrypt_roundtrip() {
		$plaintext = "-----BEGIN EC PRIVATE KEY-----\nMHQCAQ==\n-----END EC PRIVATE KEY-----";

		$encrypted = Encryption::encrypt( $plaintext );
		$decrypted = Encryption::decrypt( $encrypted );

		$this->assertSame( $plaintext, $decrypted );
	}

	/**
	 * Test that the ciphertext does not contain the plaintext.
	 *
	 * @covers ::encrypt
	 */
	public function test_encrypted_value_is_not_plaintext() {
		$plaintext = 'super-secret-private-key-material';

		$encrypted = Encryption::encrypt( $plaintext );

		$this->assertNotSame( $plaintext, $encrypted );
		$this->assertStringNotContainsString( $plaintext, $encrypted );
	}

	/**
	 * Test that each encryption uses a fresh nonce but both decrypt identically.
	 *
	 * @covers ::encrypt
	 * @covers ::decrypt
	 */
	public function test_encrypt_uses_random_nonce() {
		$plaintext = 'identical-input';

		$first  = Encryption::encrypt( $plaintext );
		$second = Encryption::encrypt( $plaintext );

		$this->assertNotSame( $first, $second, 'Ciphertexts must differ due to random nonce.' );
		$this->assertSame( $plaintext, Encryption::decrypt( $first ) );
		$this->assertSame( $plaintext, Encryption::decrypt( $second ) );
	}

	/**
	 * Test that decrypting non-ciphertext returns false rather than throwing.
	 *
	 * @covers ::decrypt
	 */
	public function test_decrypt_returns_false_on_garbage() {
		$this->assertFalse( Encryption::decrypt( 'not-valid-base64-ciphertext!!' ) );
		$this->assertFalse( Encryption::decrypt( '' ) );
	}

	/**
	 * Test that the derived key is 32 bytes and deterministic for the same material.
	 *
	 * @covers ::derive_key
	 */
	public function test_derive_key_is_deterministic_and_correct_length() {
		$a = Encryption::derive_key( 'some-material' );
		$b = Encryption::derive_key( 'some-material' );
		$c = Encryption::derive_key( 'different-material' );

		$this->assertSame( SODIUM_CRYPTO_SECRETBOX_KEYBYTES, strlen( $a ) );
		$this->assertSame( $a, $b, 'Same material must derive the same key.' );
		$this->assertNotSame( $a, $c, 'Different material must derive a different key.' );
	}
}
