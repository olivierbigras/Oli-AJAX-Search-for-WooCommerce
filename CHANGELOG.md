# Changelog

All notable changes to Oli AJAX Search for WooCommerce are documented in this file.

## 0.1.1 - 2026-10-20

### Added

- TranslatePress: store index languages as URL slugs, translate long descriptions via the TRP renderer, skip dynamic rewrite of the suggestion panel, disable TRP search rewrite on our results page, and prepare cron/AJAX indexing so permalinks keep a language prefix.
- Fuzzy matching levels: off, soft, normal, hard.
- Search WooCommerce GTIN / UPC / EAN / ISBN values.
- Separate Brands suggestion group and a dedicated brand result limit.
- Include/exclude product filter mode for product and category IDs.
- Optional daily scheduled index rebuild.
- Exact variation SKU match swaps the suggestion URL, SKU, and price to that variation.
- Frontend: query-term highlight, darkened page behind suggestions, loading indicator, configurable mobile breakpoint, max form width, optional submit label, and custom “no results” / “see all” text.
- Saving one settings tab no longer resets checkboxes on the other tabs.

### Changed

- Attributes and brands are listed as separate suggestion groups.

## 0.1.0 - 2026-10-20

### Added

- Live AJAX search bar with grouped suggestions for products, categories, tags, attributes/brands, posts, and pages.
- Product suggestion details: image, price (via WooCommerce price filters), SKU, stock, short description, and hover details panel.
- Search across title, content, excerpt, SKU (including variation SKUs), attributes, custom fields, and taxonomies.
- Custom inverted index in dedicated database tables, built in background batches with Action Scheduler or WP-Cron.
- Incremental reindexing on product save, stock changes, term edits, and TranslatePress translation saves.
- Fuzzy matching, synonyms, stopwords, exclusions, and configurable relevance weights.
- Layouts: classic bar, expanding icon, and mobile full-screen overlay.
- Shortcode, widget, Gutenberg block, navigation menu item, and automatic replacement of common theme search forms (including Storefront, Astra, and Kadence).
- Local search analytics (top queries and no-result queries). No remote tracking.
- TranslatePress support: per-language index, current-language search, translated titles/descriptions, and language-prefixed URLs.
- WPML and Polylang compatibility for languages, translated object IDs, and permalinks.
- French translation, Plugin Check-oriented sanitization/escaping, unit tests, and GitHub Actions CI.
