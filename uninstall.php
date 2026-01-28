<?php
/**
 * Uninstall script for DID Web plugin
 *
 * This file runs when the plugin is deleted via the WordPress admin.
 * It cleans up all options stored by the plugin.
 *
 * @package Did_Web
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete site DID options.
delete_option( 'did_web_handle' );
delete_option( 'did_web_pds_endpoint' );
delete_option( 'did_web_public_key_multibase' );
delete_option( 'did_web_private_key' );
delete_option( 'did_web_public_key' );

// Delete plugin identity options.
delete_option( 'did_plugin_identity_private_key' );
delete_option( 'did_plugin_identity_public_key' );
delete_option( 'did_plugin_identity_public_key_multibase' );
