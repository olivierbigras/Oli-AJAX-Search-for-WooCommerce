=== Oli AJAX Search for WooCommerce ===
Contributors: bigrat95
Donate link: https://olivierbigras.com/
Tags: woocommerce, ajax search, live search, product search, autocomplete
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 9.0
WC tested up to: 11.2

Fast live AJAX search for WooCommerce with a custom index, typo tolerance, and first-class TranslatePress support.

== Description ==

Oli AJAX Search for WooCommerce adds a live search bar with instant suggestions. It uses its own inverted index so large catalogs stay fast, and it indexes translated product titles and descriptions per language.

= Suggestions =

* Products with image, price, SKU, stock status, and short description
* Categories, tags, attributes, and brands
* Posts and pages
* A "See all results" link that uses the same relevance on the WooCommerce results page
* Optional details panel on hover, including add to cart

= Search scope =

* Title, content, excerpt
* SKU, including variation SKUs
* Attributes, custom fields, and taxonomies
* Fuzzy matching / typo tolerance
* Synonyms, stopwords, and exclusions
* Configurable relevance weights

= Index =

The plugin stores documents in its own database tables and builds them in background batches with Action Scheduler (when WooCommerce provides it) or WP-Cron. Product saves and stock changes update the index incrementally. A rebuild button with progress is available in WooCommerce → Oli AJAX Search.

= Placement =

* Shortcode: `[oli_ajax_search]`
* Widget: Oli AJAX Search
* Gutenberg block: Oli AJAX Search
* Navigation menu item
* Optional replacement of theme and WooCommerce search forms (Storefront, Astra, Kadence, and common `search-form` markup)

= Layouts =

* Classic search bar
* Icon that expands
* Mobile full-screen overlay

= Multilingual and prices =

* TranslatePress: index and display translated titles/descriptions, search in the current language, translated URLs, language-prefixed endpoints
* WPML and Polylang: language detection, translated object IDs where those plugins store them, language permalinks
* Prices are rendered with WooCommerce price APIs so currency plugins can filter them

Search analytics (top queries and queries with no results) are stored locally. The plugin does not phone home, track users remotely, or display upsells.

== Installation ==

1. Upload the `oli-ajax-search` folder to `/wp-content/plugins/`, or install the zip from Plugins → Add New.
2. Activate the plugin. WooCommerce 9.0 or newer is required.
3. Go to WooCommerce → Oli AJAX Search and click Rebuild index.
4. Add the shortcode, block, widget, or menu item, or keep automatic theme form replacement enabled.

== Frequently Asked Questions ==

= Does this replace the WooCommerce search results page? =

Yes, when that option is enabled. Submitting the form or using "See all results" keeps the same relevance order.

= How is TranslatePress supported? =

Each published language gets its own index rows. Titles and descriptions are translated with TranslatePress APIs (and the dictionary tables as a fallback). Suggestion URLs are converted with the TranslatePress URL converter.

= Are any data sent to remote servers? =

No. Analytics stay in your database. There are no tracking pixels and no external API calls.

= What is the shortcode? =

`[oli_ajax_search layout="classic" placeholder="Search products…"]`

Layout can be `classic` or `icon`.

== Changelog ==

= 0.1.0 =
* Initial release: live suggestions, custom index, multilingual support, analytics, and theme form replacement.

== Upgrade Notice ==

= 0.1.0 =
Initial public version.
