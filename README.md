# Oli AJAX Search for WooCommerce

WordPress plugin that adds a fast live AJAX search bar to WooCommerce stores.

- **Display name:** Oli AJAX Search for WooCommerce
- **Slug / text domain:** `oli-ajax-search`
- **Author:** [Olivier Bigras](https://olivierbigras.com/) ([wordpress.org/bigrat95](https://profiles.wordpress.org/bigrat95/), [github.com/olivierbigras](https://github.com/olivierbigras))
- **License:** GPLv2 or later
- **Requires:** WordPress 6.5+, PHP 7.4+, WooCommerce 9.0+
- **Tested up to:** WordPress 7.1, WooCommerce 11.2

## Features

Live suggestions (products with image, price, SKU, stock, excerpt; categories; tags; attributes/brands; posts; pages), a custom inverted index, fuzzy matching, synonyms, stopwords, exclusions, relevance weights, shortcode/widget/block/menu item, theme form replacement, hover details panel, local analytics, TranslatePress (plus WPML/Polylang), and WooCommerce price filters for multicurrency.

See `readme.txt` for the WordPress.org description and `CHANGELOG.md` for release notes.

## Development

```bash
composer install
composer lint
composer phpcs
composer test
```

Prefix: `oli_as_` / `Oli_AS`. No remote calls, tracking, or upsells.
