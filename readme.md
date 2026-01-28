# DID Web

- Contributors: pfefferle
- Donate link: https://notiz.blog/donate/
- Tags: did, decentralized, identity, bluesky, atproto, fediverse
- Requires at least: 5.0
- Tested up to: 6.7
- Stable tag: 1.0.0
- Requires PHP: 7.4
- License: GPL-2.0-or-later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

Decentralized Identifier (DID) support for WordPress. Enables your site to serve DID documents with AT Protocol/Bluesky compatibility.

## Description

This plugin allows your WordPress site to serve a DID (Decentralized Identifier) document at `/.well-known/did.json`, following the `did:web` method specification. It's designed with Bluesky/AT Protocol compatibility in mind.

### What is a DID?

A Decentralized Identifier (DID) is a new type of identifier that enables verifiable, decentralized digital identity. DIDs are URIs that associate a DID subject with a DID document allowing trustable interactions with that subject.

### Features

* Serves DID documents at `/.well-known/did.json`
* Generates DID identifiers based on your domain (`did:web:yourdomain.com`)
* Admin settings interface for easy configuration
* Key management (with experimental secp256k1 support)
* AT Protocol (Bluesky) compatible document structure
* Configurable verification methods
* Personal Data Server (PDS) endpoint configuration
* Support for custom handles (alsoKnownAs)

### Bluesky/AT Protocol Integration

This plugin is designed to be compatible with Bluesky and the AT Protocol. To use your WordPress site as your DID for Bluesky:

1. Configure your DID document with a valid secp256k1 public key
2. Set your Bluesky handle in the settings
3. Point your Bluesky account to use your `did:web` identifier
4. Ensure your `/.well-known/did.json` is publicly accessible

**Note**: Full Bluesky integration requires:

* A properly formatted secp256k1 public key
* HTTPS enabled on your site
* Proper CORS headers (handled automatically)

### DID Document Structure

Your DID document will be accessible at `https://yourdomain.com/.well-known/did.json`

Example structure:

```json
{
  "@context": [
    "https://www.w3.org/ns/did/v1",
    "https://w3id.org/security/multikey/v1",
    "https://w3id.org/security/suites/secp256k1-2019/v1"
  ],
  "id": "did:web:yourdomain.com",
  "alsoKnownAs": [
    "at://username.bsky.social"
  ],
  "verificationMethod": [
    {
      "id": "did:web:yourdomain.com#atproto",
      "type": "Multikey",
      "controller": "did:web:yourdomain.com",
      "publicKeyMultibase": "zQ3sh..."
    }
  ],
  "service": [
    {
      "id": "#atproto_pds",
      "type": "AtprotoPersonalDataServer",
      "serviceEndpoint": "https://yourdomain.com"
    }
  ]
}
```

### Filters and Hooks

#### `did_web_document`

Modify the DID document before it's returned.

```php
add_filter( 'did_web_document', function( $document ) {
    // Add custom properties
    $document['custom'] = 'value';
    return $document;
} );
```

## Frequently Asked Questions

### What is did:web?

`did:web` is a DID method that uses the web's existing infrastructure. Your DID is derived from your domain name (e.g., `did:web:example.com`).

### Do I need HTTPS?

Yes, HTTPS is required for production use to ensure DID document integrity and prevent man-in-the-middle attacks.

### How do I generate keys?

The plugin includes experimental key generation features. For production use, we recommend generating your secp256k1 keypair using a trusted cryptographic library and entering the public key in multibase format in the settings.

### Is this compatible with Bluesky?

Yes! The plugin generates DID documents compatible with the AT Protocol used by Bluesky. You'll need a valid secp256k1 public key for full compatibility.

### What about subdirectory installations?

The plugin handles subdirectory installations automatically. If your site is at `example.com/blog`, your DID will be `did:web:example.com:blog`.

### Can I customize the DID document?

Yes, use the `did_web_document` filter to modify the document before it's served.

## Changelog

Project and support maintained on GitHub at [pfefferle/wordpress-did-web](https://github.com/pfefferle/wordpress-did-web).

### 1.0.0

* Initial release
* Basic DID document serving at `/.well-known/did.json`
* Admin configuration interface
* Experimental key generation with secp256k1 support
* AT Protocol/Bluesky compatibility structure
* Support for custom handles (alsoKnownAs)
* PDS endpoint configuration

## Installation

Follow the normal instructions for [installing WordPress plugins](https://developer.wordpress.org/plugins/plugin-basics/installing-plugins/).

### Automatic Plugin Installation

To add a WordPress Plugin using the [built-in plugin installer](https://developer.wordpress.org/plugins/plugin-basics/installing-plugins/#automatic-plugin-installation):

1. Go to Plugins > Add New.
2. Type "`did-web`" into the **Search Plugins** box.
3. Find the WordPress Plugin you wish to install.
4. Click **Install Now** to install the WordPress Plugin.
5. The resulting installation screen will list the installation as successful or note any problems during the install.
6. If successful, click **Activate Plugin** to activate it.

### Manual Plugin Installation

To install a WordPress Plugin manually:

1. Download your WordPress Plugin to your desktop.
    * Download from [the WordPress directory](https://wordpress.org/plugins/did-web/)
    * Download from [GitHub](https://github.com/pfefferle/wordpress-did-web/releases)
2. If downloaded as a zip archive, extract the Plugin folder to your desktop.
3. With your FTP program, upload the Plugin folder to the `wp-content/plugins` folder in your WordPress directory online.
4. Go to Plugins screen and find the newly uploaded Plugin in the list.
5. Click **Activate** to activate it.

## Upgrade Notice

### 1.0.0

Initial release.

## Resources

* [DID Specification](https://www.w3.org/TR/did-core/)
* [did:web Method Specification](https://w3c-ccg.github.io/did-method-web/)
* [AT Protocol](https://atproto.com/)
* [Bluesky](https://bsky.app/)
