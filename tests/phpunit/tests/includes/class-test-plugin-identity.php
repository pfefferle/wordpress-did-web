<?php
/**
 * Test Plugin Identity class.
 *
 * @package Did_Web
 */

namespace Did_Web\Tests;

use Did_Web\Plugin_Identity;

/**
 * Test class for Plugin_Identity.
 *
 * @coversDefaultClass \Did_Web\Plugin_Identity
 */
class Test_Plugin_Identity extends Did_Web_Testcase {

	/**
	 * Test PLUGIN_DID constant.
	 *
	 * @covers ::PLUGIN_DID
	 */
	public function test_plugin_did_constant() {
		$this->assertStringStartsWith( 'did:web:', Plugin_Identity::PLUGIN_DID );
	}

	/**
	 * Test has_identity returns false initially.
	 *
	 * @covers ::has_identity
	 */
	public function test_has_identity_returns_false_initially() {
		$this->assertFalse( Plugin_Identity::has_identity() );
	}

	/**
	 * Test get_did_document returns valid structure.
	 *
	 * @covers ::get_did_document
	 */
	public function test_get_did_document_structure() {
		$document = Plugin_Identity::get_did_document();

		$this->assertIsArray( $document );
		$this->assertArrayHasKey( '@context', $document );
		$this->assertArrayHasKey( 'id', $document );
		$this->assertArrayHasKey( 'verificationMethod', $document );
		$this->assertArrayHasKey( 'service', $document );
	}

	/**
	 * Test get_did_document id matches constant.
	 *
	 * @covers ::get_did_document
	 */
	public function test_get_did_document_id() {
		$document = Plugin_Identity::get_did_document();

		$this->assertEquals( Plugin_Identity::PLUGIN_DID, $document['id'] );
	}

	/**
	 * Test get_did_document_json returns valid JSON.
	 *
	 * @covers ::get_did_document_json
	 */
	public function test_get_did_document_json() {
		$json = Plugin_Identity::get_did_document_json();

		$this->assertIsString( $json );

		$decoded = json_decode( $json, true );
		$this->assertNotNull( $decoded );
	}

	/**
	 * Test delete_identity removes all options.
	 *
	 * @covers ::delete_identity
	 */
	public function test_delete_identity() {
		// Set some values first.
		update_option( Plugin_Identity::OPTION_PLUGIN_PRIVATE_KEY, 'test' );
		update_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY, 'test' );
		update_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE, 'test' );

		$result = Plugin_Identity::delete_identity();

		$this->assertTrue( $result );
		$this->assertFalse( get_option( Plugin_Identity::OPTION_PLUGIN_PRIVATE_KEY, false ) );
		$this->assertFalse( get_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY, false ) );
		$this->assertFalse( get_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE, false ) );
	}

	/**
	 * Test export_public_key returns false without identity.
	 *
	 * @covers ::export_public_key
	 */
	public function test_export_public_key_without_identity() {
		$this->assertFalse( Plugin_Identity::export_public_key() );
	}

	/**
	 * Test export_public_key returns data with identity.
	 *
	 * @covers ::export_public_key
	 */
	public function test_export_public_key_with_identity() {
		update_option( Plugin_Identity::OPTION_PLUGIN_PRIVATE_KEY, 'private_key' );
		update_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY, 'public_key' );
		update_option( Plugin_Identity::OPTION_PLUGIN_PUBLIC_KEY_MULTIBASE, 'multibase_key' );

		$result = Plugin_Identity::export_public_key();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'pem', $result );
		$this->assertArrayHasKey( 'multibase', $result );
		$this->assertArrayHasKey( 'did', $result );
	}
}
