=== DID Web ===
Contributors: pfefferle
Donate link: https://notiz.blog/donate/
Tags: did, decentralized, identity, bluesky, atproto
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Decentralized Identifier (DID) support for WordPress. Enables your site to serve DID documents with AT Protocol/Bluesky compatibility.

== Description ==

This plugin allows your WordPress site to serve a DID (Decentralized Identifier) document at `/.well-known/did.json`, following the `did:web` method specification. It's designed with Bluesky/AT Protocol compatibility in mind.

= What is a DID? =

A Decentralized Identifier (DID) is a new type of identifier that enables verifiable, decentralized digital identity. DIDs are URIs that associate a DID subject with a DID document allowing trustable interactions with that subject.

= Features =

* Serves DID documents at `/.well-known/did.json`
* Generates DID identifiers based on your domain (`did:web:yourdomain.com`)
* Admin settings interface for easy configuration
* Key management (with experimental secp256k1 support)
* AT Protocol (Bluesky) compatible document structure
* Configurable verification methods
* Personal Data Server (PDS) endpoint configuration
* Support for custom handles (alsoKnownAs)

= Bluesky/AT Protocol Integration =

This plugin is designed to be compatible with Bluesky and the AT Protocol. To use your WordPress site as your DID for Bluesky:

1. Configure your DID document with a valid secp256k1 public key
2. Set your Bluesky handle in the settings
3. Point your Bluesky account to use your `did:web` identifier
4. Ensure your `/.well-known/did.json` is publicly accessible

== Installation ==

1. Upload the `did-web` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > DID Web to configure your DID

== Frequently Asked Questions ==

= What is did:web? =

`did:web` is a DID method that uses the web's existing infrastructure. Your DID is derived from your domain name (e.g., `did:web:example.com`).

= Do I need HTTPS? =

Yes, HTTPS is required for production use to ensure DID document integrity and prevent man-in-the-middle attacks.

= How do I generate keys? =

The plugin includes experimental key generation features. For production use, we recommend generating your secp256k1 keypair using a trusted cryptographic library and entering the public key in multibase format in the settings.

= Is this compatible with Bluesky? =

Yes! The plugin generates DID documents compatible with the AT Protocol used by Bluesky. You'll need a valid secp256k1 public key for full compatibility.

== Screenshots ==

1. DID Web settings page
2. DID document preview
3. Key management section

== Changelog ==

= 1.0.0 =
* Initial release
* Basic DID document serving for WordPress sites
* Admin configuration interface
* Experimental key generation with secp256k1 support
* AT Protocol/Bluesky compatibility structure

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Privacy Policy ==

This plugin serves a public DID document at `/.well-known/did.json`. The document contains:

* Your site's DID identifier (based on your domain)
* Your configured Bluesky handle (if set)
* Your public key (if configured)
* Your PDS endpoint URL

No personal data is collected or transmitted to external services by this plugin.
