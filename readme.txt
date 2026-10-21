=== Oli AJAX Search for WooCommerce ===
Contributors: bigrat95
Donate link: https://olivierbigras.com/
Tags: woocommerce, ajax search, live search, product search, autocomplete
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 9.0
WC tested up to: 11.2

Fast live AJAX search for WooCommerce with a custom index, typo tolerance, and multilingual support.

== Description ==

Oli AJAX Search for WooCommerce adds a live search bar with instant suggestions. It uses its own inverted index so large catalogs stay fast, and it indexes translated product titles and descriptions per language.

= Suggestions =

* Products with image, price, SKU, stock status, and short description
* Categories, tags, attributes, and brands
* Posts and pages
* A "See all results" link that uses the same relevance on the WooCommerce results page
* Optional details panel on hover, including add to cart
* Full-screen overlay on mobile

= Search scope =

* Title, content, excerpt
* SKU, including variation SKUs
* Attributes, custom fields, and taxonomies
* GTIN / UPC / EAN / ISBN
* Fuzzy matching / typo tolerance
* Synonyms, stopwords, and include/exclude filters

= Index =

The plugin stores documents in its own database tables and builds them in background batches with Action Scheduler (when WooCommerce provides it) or WP-Cron. Product saves and stock changes update the index incrementally. A rebuild button with progress is available in WooCommerce → Oli AJAX Search.

= Placement =

* Shortcode: `[oli_search]`
* Widget: Oli AJAX Search bar
* Gutenberg blocks: search form and navigation item
* Navigation menu item
* Elementor widget
* Optional replacement of theme and WooCommerce search forms

= Multilingual and prices =

* TranslatePress, WPML, Polylang, and qTranslate-XT
* Prices are rendered with WooCommerce price APIs so currency plugins can filter them

Search analytics are stored locally. The plugin does not phone home.

== Installation ==

1. Upload the `oli-ajax-search` folder to `/wp-content/plugins/`, or install the zip from Plugins → Add New.
2. Activate the plugin. WooCommerce 9.0 or newer is required.
3. Go to WooCommerce → Oli AJAX Search and click Rebuild index.
4. Add the shortcode, block, widget, or menu item, or enable theme form replacement.

== Frequently Asked Questions ==

= Does this replace the WooCommerce search results page? =

Yes, when that option is enabled. Submitting the form or using "See all results" keeps the same relevance order.

= Are any data sent to remote servers? =

No, not by default. Analytics stay in your database. There are no tracking pixels and no plugin-initiated API calls. See **External services** below.

= What is the shortcode? =

`[oli_search layout="classic"]`

Layouts: `classic`, `icon`, `icon-flexible`, `icon-flexible-inv`.

== External services ==

This plugin does not phone home. Search suggestions, the product index, and analytics stay on your WordPress site. It does not call olivierbigras.com, PayPal, Google, Cloudflare Turnstile, Carto, Leaflet, Google Sheets, Google Fonts, or Adobe Typekit.

= Optional custom preloader image =

If you paste a preloader image URL in WooCommerce → Oli AJAX Search → Search bar, the visitor's browser requests that file from the host you entered. The plugin sends no other data to that host. Leave the field empty to use the built-in CSS spinner (no remote request).

= Author and donate links =

The plugin header and this readme link to https://olivierbigras.com/. Those links are documentation only. The plugin never requests that site.

== Changelog ==

= 0.2.1 =
* Escaped remaining widget, admin, and Elementor output.
* Moved the Menus-screen script into an enqueued file.
* Documented external services (none by default; optional preloader URL is opt-in).
* Upgrade copies any remapped options to the current oli_as_ keys, then deletes the old keys.

= 0.2.0 =
* Settings tabs: Starting, Search bar, Autocomplete, Search config, Analytics, Indexer, Troubleshooting.
* Public search rate limit, catalog-visibility filtering, conditional assets, query cache, CSV export, scheduler, Elementor, qTranslate-XT, fr_CA.

= 0.1.1 =
* Stronger TranslatePress indexing, fuzzy levels, GTIN, brands, include/exclude, scheduled rebuild.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.2.1 =
Settings and index data are kept. Rebuild the index only if suggestions look stale.

= 0.2.0 =
Rebuild the index after updating so visibility rules and new filter fields apply.
