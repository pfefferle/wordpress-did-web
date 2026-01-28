<?php
/**
 * Test DID Document class.
 *
 * @package Did_Web
 */

namespace Did_Web\Tests;

use Did_Web\DID_Document;

/**
 * Test class for DID_Document.
 *
 * @coversDefaultClass \Did_Web\DID_Document
 */
class Test_DID_Document extends Did_Web_Testcase {

	/**
	 * Test get_did_identifier returns correct format.
	 *
	 * @covers ::get_did_identifier
	 */
	public function test_get_did_identifier() {
		$did = DID_Document::get_did_identifier();

		$this->assertStringStartsWith( 'did:web:', $did );
	}

	/**
	 * Test generate returns valid document structure.
	 *
	 * @covers ::generate
	 */
	public function test_generate_returns_valid_structure() {
		$document = DID_Document::generate();

		$this->assertIsArray( $document );
		$this->assertArrayHasKey( '@context', $document );
		$this->assertArrayHasKey( 'id', $document );
		$this->assertArrayHasKey( 'service', $document );
	}

	/**
	 * Test generate includes context.
	 *
	 * @covers ::generate
	 */
	public function test_generate_includes_context() {
		$document = DID_Document::generate();

		$this->assertContains( 'https://www.w3.org/ns/did/v1', $document['@context'] );
	}

	/**
	 * Test generate with handle adds alsoKnownAs.
	 *
	 * @covers ::generate
	 */
	public function test_generate_with_handle() {
		update_option( 'did_web_handle', 'test.bsky.social' );

		$document = DID_Document::generate();

		$this->assertArrayHasKey( 'alsoKnownAs', $document );
		$this->assertContains( 'at://test.bsky.social', $document['alsoKnownAs'] );
	}

	/**
	 * Test generate with public key adds verification method.
	 *
	 * @covers ::generate
	 */
	public function test_generate_with_public_key() {
		update_option( 'did_web_public_key_multibase', 'zQ3shTestKey123' );

		$document = DID_Document::generate();

		$this->assertArrayHasKey( 'verificationMethod', $document );
		$this->assertCount( 1, $document['verificationMethod'] );
		$this->assertEquals( 'zQ3shTestKey123', $document['verificationMethod'][0]['publicKeyMultibase'] );
	}

	/**
	 * Test get_json returns valid JSON.
	 *
	 * @covers ::get_json
	 */
	public function test_get_json() {
		$json = DID_Document::get_json();

		$this->assertIsString( $json );

		$decoded = json_decode( $json, true );
		$this->assertNotNull( $decoded );
		$this->assertArrayHasKey( 'id', $decoded );
	}

	/**
	 * Test did_web_document filter.
	 *
	 * @covers ::generate
	 */
	public function test_did_web_document_filter() {
		add_filter(
			'did_web_document',
			function ( $document ) {
				$document['custom'] = 'test_value';
				return $document;
			}
		);

		$document = DID_Document::generate();

		$this->assertArrayHasKey( 'custom', $document );
		$this->assertEquals( 'test_value', $document['custom'] );
	}

	/**
	 * Test service endpoint uses PDS option.
	 *
	 * @covers ::generate
	 */
	public function test_service_endpoint_uses_pds_option() {
		update_option( 'did_web_pds_endpoint', 'https://custom-pds.example.com' );

		$document = DID_Document::generate();

		$this->assertEquals(
			'https://custom-pds.example.com',
			$document['service'][0]['serviceEndpoint']
		);
	}
}
