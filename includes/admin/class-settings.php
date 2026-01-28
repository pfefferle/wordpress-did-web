<?php
/**
 * Admin Settings Page
 *
 * @package Did_Web
 */

namespace Did_Web\Admin;

use Did_Web\Crypto;
use Did_Web\DID_Document;
use Did_Web\Plugin_Identity;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class
 */
class Settings {

	/**
	 * Initialize the settings
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add settings menu
	 */
	public static function add_menu() {
		add_options_page(
			__( 'DID Web Settings', 'did-web' ),
			__( 'DID Web', 'did-web' ),
			'manage_options',
			'did-web-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		// DID Configuration Section
		add_settings_section(
			'did_web_config',
			__( 'DID Configuration', 'did-web' ),
			array( __CLASS__, 'config_section_callback' ),
			'did-web-settings'
		);

		// Handle field
		register_setting( 'did_web_settings', 'did_web_handle' );
		add_settings_field(
			'did_web_handle',
			__( 'Bluesky Handle', 'did-web' ),
			array( __CLASS__, 'handle_field_callback' ),
			'did-web-settings',
			'did_web_config'
		);

		// PDS Endpoint field
		register_setting( 'did_web_settings', 'did_web_pds_endpoint' );
		add_settings_field(
			'did_web_pds_endpoint',
			__( 'PDS Endpoint', 'did-web' ),
			array( __CLASS__, 'pds_endpoint_field_callback' ),
			'did-web-settings',
			'did_web_config'
		);

		// Public Key Multibase field
		register_setting( 'did_web_settings', 'did_web_public_key_multibase' );
		add_settings_field(
			'did_web_public_key_multibase',
			__( 'Public Key (Multibase)', 'did-web' ),
			array( __CLASS__, 'public_key_multibase_field_callback' ),
			'did-web-settings',
			'did_web_config'
		);

		// Key Management Section
		add_settings_section(
			'did_web_keys',
			__( 'Key Management', 'did-web' ),
			array( __CLASS__, 'keys_section_callback' ),
			'did-web-settings'
		);

		// Plugin Identity Section
		add_settings_section(
			'did_web_plugin_identity',
			__( 'Plugin Identity', 'did-web' ),
			array( __CLASS__, 'plugin_identity_section_callback' ),
			'did-web-settings'
		);
	}

	/**
	 * Config section callback
	 */
	public static function config_section_callback() {
		$did = DID_Document::get_did_identifier();
		$url = home_url( '/.well-known/did.json' );
		echo '<p>' . esc_html__( 'Configure your DID document settings.', 'did-web' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Your DID:', 'did-web' ) . '</strong> <code>' . esc_html( $did ) . '</code></p>';
		echo '<p><strong>' . esc_html__( 'DID Document URL:', 'did-web' ) . '</strong> <a href="' . esc_url( $url ) . '" target="_blank"><code>' . esc_html( $url ) . '</code></a></p>';
	}

	/**
	 * Handle field callback
	 */
	public static function handle_field_callback() {
		$value = get_option( 'did_web_handle', '' );
		echo '<input type="text" name="did_web_handle" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="username.bsky.social" />';
		echo '<p class="description">' . esc_html__( 'Your Bluesky handle (e.g., username.bsky.social)', 'did-web' ) . '</p>';
	}

	/**
	 * PDS endpoint field callback
	 */
	public static function pds_endpoint_field_callback() {
		$value = get_option( 'did_web_pds_endpoint', home_url() );
		echo '<input type="url" name="did_web_pds_endpoint" value="' . esc_attr( $value ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'The AT Protocol Personal Data Server endpoint. Usually your site URL or a Bluesky PDS.', 'did-web' ) . '</p>';
	}

	/**
	 * Public key multibase field callback
	 */
	public static function public_key_multibase_field_callback() {
		$value = get_option( 'did_web_public_key_multibase', '' );
		echo '<input type="text" name="did_web_public_key_multibase" value="' . esc_attr( $value ) . '" class="large-text" placeholder="zQ3sh..." />';
		echo '<p class="description">' . esc_html__( 'Your secp256k1 public key in multibase format (base58btc with "z" prefix). Required for AT Protocol verification.', 'did-web' ) . '</p>';
	}

	/**
	 * Keys section callback
	 */
	public static function keys_section_callback() {
		echo '<p>' . esc_html__( 'Manage cryptographic keys for your DID.', 'did-web' ) . '</p>';

		if ( Crypto::has_keypair() ) {
			echo '<p class="notice notice-success inline">' . esc_html__( 'Keypair exists in database.', 'did-web' ) . '</p>';

			$public_key = Crypto::get_public_key();
			echo '<h4>' . esc_html__( 'Public Key (PEM)', 'did-web' ) . '</h4>';
			echo '<textarea readonly class="large-text code" rows="8">' . esc_textarea( $public_key ) . '</textarea>';

			echo '<p>';
			echo '<button type="button" class="button button-secondary" onclick="if(confirm(\'' . esc_js( __( 'Are you sure you want to delete the keypair? This cannot be undone.', 'did-web' ) ) . '\')) { document.getElementById(\'delete_keypair_form\').submit(); }">';
			echo esc_html__( 'Delete Keypair', 'did-web' );
			echo '</button>';
			echo '</p>';

			echo '<form id="delete_keypair_form" method="post" action="" style="display:none;">';
			wp_nonce_field( 'did_web_delete_keypair', 'did_web_delete_keypair_nonce' );
			echo '<input type="hidden" name="did_web_action" value="delete_keypair" />';
			echo '</form>';
		} else {
			echo '<p class="notice notice-warning inline">' . esc_html__( 'No keypair found.', 'did-web' ) . '</p>';
			echo '<p>' . esc_html__( 'Note: Automatic key generation requires secp256k1 support. For now, please generate your keys externally and enter the public key in multibase format above.', 'did-web' ) . '</p>';

			echo '<form method="post" action="">';
			wp_nonce_field( 'did_web_generate_keypair', 'did_web_generate_keypair_nonce' );
			echo '<input type="hidden" name="did_web_action" value="generate_keypair" />';
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Generate Keypair (Experimental)', 'did-web' ) . '</button>';
			echo '</form>';
		}
	}

	/**
	 * Plugin identity section callback
	 */
	public static function plugin_identity_section_callback() {
		echo '<p>' . esc_html__( 'This plugin has its own DID identity for verification and authenticity. All keys are stored securely in WordPress options (never in filesystem).', 'did-web' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Plugin DID:', 'did-web' ) . '</strong> <code>' . esc_html( Plugin_Identity::PLUGIN_DID ) . '</code></p>';
		echo '<p><strong>' . esc_html__( 'Virtual Endpoint:', 'did-web' ) . '</strong> <a href="' . esc_url( home_url( '/did-plugin/did.json' ) ) . '" target="_blank"><code>' . esc_html( home_url( '/did-plugin/did.json' ) ) . '</code></a></p>';

		if ( Plugin_Identity::has_identity() ) {
			echo '<p class="notice notice-success inline">' . esc_html__( 'Plugin identity exists.', 'did-web' ) . '</p>';

			$public_key_data = Plugin_Identity::export_public_key();

			echo '<h4>' . esc_html__( 'Plugin Public Key (Multibase)', 'did-web' ) . '</h4>';
			echo '<input type="text" readonly class="large-text code" value="' . esc_attr( $public_key_data['multibase'] ) . '" />';

			echo '<p>';
			echo '<button type="button" class="button button-secondary" onclick="if(confirm(\'' . esc_js( __( 'Regenerate plugin identity? This will create a new DID document.', 'did-web' ) ) . '\')) { document.getElementById(\'regenerate_plugin_identity_form\').submit(); }">';
			echo esc_html__( 'Regenerate Plugin Identity', 'did-web' );
			echo '</button>';
			echo '</p>';

			echo '<form id="regenerate_plugin_identity_form" method="post" action="" style="display:none;">';
			wp_nonce_field( 'did_web_regenerate_plugin_identity', 'did_web_regenerate_plugin_identity_nonce' );
			echo '<input type="hidden" name="did_web_action" value="regenerate_plugin_identity" />';
			echo '</form>';

			// Show current DID document
			echo '<h4>' . esc_html__( 'Plugin DID Document (Virtual)', 'did-web' ) . '</h4>';
			echo '<p class="description">' . esc_html__( 'Served dynamically from WordPress options. Keys are never stored in filesystem.', 'did-web' ) . '</p>';
			echo '<pre class="code" style="max-height: 300px; overflow: auto; padding: 10px; background: #f5f5f5;">' . esc_html( Plugin_Identity::get_did_document_json() ) . '</pre>';
		} else {
			echo '<p class="notice notice-warning inline">' . esc_html__( 'Plugin identity not generated yet.', 'did-web' ) . '</p>';

			echo '<form method="post" action="">';
			wp_nonce_field( 'did_web_generate_plugin_identity', 'did_web_generate_plugin_identity_nonce' );
			echo '<input type="hidden" name="did_web_action" value="generate_plugin_identity" />';
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Generate Plugin Identity', 'did-web' ) . '</button>';
			echo '</form>';
		}
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		// Handle form actions
		if ( isset( $_POST['did_web_action'] ) ) {
			if ( $_POST['did_web_action'] === 'generate_keypair' && check_admin_referer( 'did_web_generate_keypair', 'did_web_generate_keypair_nonce' ) ) {
				$keypair = Crypto::generate_keypair();
				if ( $keypair ) {
					Crypto::save_keypair( $keypair['private'], $keypair['public'] );
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Keypair generated successfully!', 'did-web' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to generate keypair. OpenSSL with secp256k1 support may not be available.', 'did-web' ) . '</p></div>';
				}
			} elseif ( $_POST['did_web_action'] === 'delete_keypair' && check_admin_referer( 'did_web_delete_keypair', 'did_web_delete_keypair_nonce' ) ) {
				Crypto::delete_keypair();
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Keypair deleted successfully!', 'did-web' ) . '</p></div>';
			} elseif ( $_POST['did_web_action'] === 'generate_plugin_identity' && check_admin_referer( 'did_web_generate_plugin_identity', 'did_web_generate_plugin_identity_nonce' ) ) {
				if ( Plugin_Identity::generate_identity() ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Plugin identity generated successfully! Keys stored securely in WordPress options.', 'did-web' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to generate plugin identity.', 'did-web' ) . '</p></div>';
				}
			} elseif ( $_POST['did_web_action'] === 'regenerate_plugin_identity' && check_admin_referer( 'did_web_regenerate_plugin_identity', 'did_web_regenerate_plugin_identity_nonce' ) ) {
				Plugin_Identity::delete_identity();
				if ( Plugin_Identity::generate_identity() ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Plugin identity regenerated successfully!', 'did-web' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to regenerate plugin identity.', 'did-web' ) . '</p></div>';
				}
			}
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'did_web_settings' );
				do_settings_sections( 'did-web-settings' );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Test Your DID Document', 'did-web' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( home_url( '/.well-known/did.json' ) ); ?>" target="_blank" class="button">
					<?php esc_html_e( 'View DID Document', 'did-web' ); ?>
				</a>
			</p>

			<h3><?php esc_html_e( 'Current DID Document', 'did-web' ); ?></h3>
			<pre class="code" style="max-height: 400px; overflow: auto; padding: 10px; background: #f5f5f5;"><?php echo esc_html( DID_Document::get_json() ); ?></pre>
		</div>
		<?php
	}
}
