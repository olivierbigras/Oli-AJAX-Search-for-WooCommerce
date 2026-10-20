# Oli AJAX Search for WooCommerce

Fast live AJAX search for WooCommerce. Independent GPL plugin with a custom index, typo tolerance, and multilingual support.

**Requires:** WordPress 6.5+, PHP 7.4+, WooCommerce 9.0+  
**Version:** 0.2.0  
**License:** GPL-2.0-or-later

## Features

- Live suggestions for products, categories, tags, brands, posts, and pages
- Hover details panel and full-screen mobile overlay
- Search in SKU, variations, attributes, taxonomies, custom fields, and GTIN
- Synonyms, stopwords, and fuzzy matching (off / soft / normal / hard)
- Background index with incremental updates and an optional scheduler
- Shortcode `[oli_search]`, widget, Gutenberg blocks, menu item, Elementor widget
- Theme search replacement for common WooCommerce themes
- TranslatePress, WPML, Polylang, and qTranslate-XT
- Local analytics (no remote tracking)

## Settings

WooCommerce → **Oli AJAX Search**

1. Starting
2. Search bar
3. Autocomplete
4. Search config
5. Analytics
6. Indexer
7. Troubleshooting

See [FEATURE-MAP.md](FEATURE-MAP.md) for the screen-by-screen correspondence with the original premium plugin used as a private behavioral reference.

## Development

```bash
composer install
composer lint
composer phpcs
composer test
```
