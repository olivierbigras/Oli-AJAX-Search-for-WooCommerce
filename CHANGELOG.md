# Changelog

All notable changes to Oli AJAX Search for WooCommerce are documented in this file.

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
