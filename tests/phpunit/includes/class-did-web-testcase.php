<?php
/**
 * Base test case for DID Web tests.
 *
 * @package Did_Web
 */

namespace Did_Web\Tests;

use WP_UnitTestCase;

/**
 * Base test case class.
 */
class Did_Web_Testcase extends WP_UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		// Flush rewrite rules for clean state.
		flush_rewrite_rules();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		parent::tear_down();

		// Clean up options.
		delete_option( 'did_web_handle' );
		delete_option( 'did_web_pds_endpoint' );
		delete_option( 'did_web_public_key_multibase' );
		delete_option( 'did_web_private_key' );
		delete_option( 'did_web_public_key' );
		delete_option( 'did_plugin_identity_private_key' );
		delete_option( 'did_plugin_identity_public_key' );
		delete_option( 'did_plugin_identity_public_key_multibase' );
	}
}
