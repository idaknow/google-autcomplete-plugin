=== WooCommerce Google Address Autocomplete (Places API) ===
Contributors: idaknow
Tags: woocommerce, checkout, address, autocomplete, google maps
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds Google Places address autocomplete to WooCommerce checkout billing and shipping address fields.

== Description ==

This plugin adds Google Places autocomplete to WooCommerce checkout address fields using `PlaceAutocompleteElement`.

Developer: [Ida De Smet](https://www.katinkaa.com)

Features:
- Autocomplete support for billing and shipping address line 1.
- Automatic fill for street, city, and postcode fields.
- Admin settings page to save your Google Maps API key.
- Configurable 2-letter region code for address suggestions (default: `nz`).
- Uses the new Places API `PlaceAutocompleteElement` and currently loads the Google Maps JavaScript API with `v=beta` to ensure this component works.

== Installation ==

1. Download the zip file
1. Upload the plugin in Wordpress
2. Activate the plugin
3. Go to `Settings > Google Autocomplete`.
4. Generate a New Places API key in the [Google Cloud Console](https://console.cloud.google.com/google/maps-apis/credentials) (free below a certain threshold), then enter it in plugin settings and save.

== Changelog ==

= 1.0.0 =
- Initial release.
