# Feature gap vs FiboSearch Pro 1.34.3

Private behavioral review of a licensed FiboSearch Pro 1.34.3 copy. No FiboSearch code, text, CSS, or assets were copied into this plugin. The zip is not in the repository.

Status column:

- **0.1.0** already shipped an independent equivalent
- **0.1.1** added in this release
- **Later** still a gap (not required for 0.1.1)

## Search form and layouts

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Placeholder, min characters, debounce | Yes | Yes |
| Classic bar and expanding icon | Yes | Yes |
| Mobile full-screen overlay | Yes (fixed 782px) | Yes (configurable breakpoint) |
| Darken page behind suggestions | No | **Added** |
| Submit button on/off and custom label | Icon only | **Added** |
| Max form width | CSS default only | **Added** |
| Loading indicator while fetching | No | **Added** (CSS spinner; no custom GIF URL) |
| Named visual skins / style library | Later | Later |
| Separate submit background/text colors | Later (accent color is used) | Later |
| Voice search | Later | Later |

## Suggestions

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Products with image, price, SKU, stock, excerpt | Yes | Yes |
| Categories, tags, attributes, posts, pages | Yes | Yes |
| Brands as their own group | Combined with attributes | **Added** |
| See all results (same relevance) | Yes | Yes |
| Custom “no results” / “see all” text | Hardcoded strings | **Added** |
| Highlight matched query terms | No | **Added** |
| Hover details panel + add to cart | Yes | Yes |
| Recent searches | localStorage | localStorage |
| Sale / featured badges | Later | Later |
| Server-side search history | Later | Later |
| “Did you mean” | Later | Later |
| Suggestion limit per group | Yes | Yes (plus brands) |

## Search scope and ranking

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Title, content, excerpt, SKU, variation SKUs | Yes | Yes |
| Attributes, taxonomies, custom fields | Yes | Yes |
| GTIN / UPC / EAN / ISBN | No | **Added** |
| Fuzzy on/off | Yes | Yes |
| Fuzzy levels (off / soft / normal / hard) | No | **Added** |
| Synonyms and stopwords | Yes | Yes |
| Product / category ID exclude list | Exclude only | **Include or exclude** |
| Out-of-stock hide | Yes | Yes |
| Relevance weights | Yes | Yes |
| Exact variation SKU opens that variation | No | **Added** |
| Rule builder (on sale, featured, stock, …) | Later | Later |

## Index

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Custom inverted index tables | Yes | Yes |
| Action Scheduler / WP-Cron batches + progress | Yes | Yes |
| Incremental product / stock / term / content updates | Yes | Yes |
| Daily scheduled rebuild | No | **Added** |
| Rebuild interval + start time picker | Later | Later |
| SHORTINIT lightweight endpoint | Later | Later |
| 100k+ catalog debugger / failure reports | Later | Later |

## Multilingual (TranslatePress is the critical path)

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Per-language index rows | Yes | Yes |
| Current-language search | Yes | Yes (slug or locale) |
| Translated titles | Yes | Yes |
| Translated long descriptions (multi-phrase HTML) | Phrase lookup only | **TRP renderer + dictionary fallback** |
| Translated suggestion URLs / language-prefixed endpoints | Yes | Yes |
| Index language keys = TRP URL slugs (`fr`, not only `fr_FR`) | Locale-ish | **Slug keys + same-language matching** |
| Do not translate SKUs / GTINs | Implicit | **SKU kept original; GTIN indexed as-is** |
| Skip dynamic JS rewrite of the suggestion panel | No | **Added** |
| Remove TRP search rewrite on our results page | No | **Added** (only when our results query runs) |
| Indexing: language-prefixed permalinks in cron/AJAX | Partial | **Admin-URL check off, machine translation init, cron `home_url`** |
| Reindex when TRP translations are saved | Yes | Yes |
| WPML / Polylang language + object IDs + permalinks | Yes | Yes |
| qTranslate-XT | Later | Later |

## Placement and theme embedding

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Shortcode, widget, Gutenberg block, menu item | Yes | Yes |
| Replace WooCommerce / `search-form` / Storefront / Astra / Kadence | Yes | Yes |
| Dedicated Elementor / Bricks / Brizy widgets | Later | Later |
| Official list of every FiboSearch theme integration | Later | Later |

## Analytics and admin

| Behavior | 0.1.0 | 0.1.1 |
| --- | --- | --- |
| Local top queries / no-result queries | Yes | Yes |
| Remote tracking / upsells / Freemius | Intentionally absent | Intentionally absent |
| Analytics CSV export | Later | Later |
| Troubleshooting / debugger screen | Later | Later |
| Settings tabs do not clobber each other | Bug (save one tab reset others) | **Fixed** |

## Pricing

Prices still go through WooCommerce (`get_price_html` / `wc_get_price_to_display`) so currency plugins can filter them. No FiboSearch-specific currency integration.

## Still later (not blocking 0.1.1)

Voice search, named skins, sale/featured badges, “did you mean”, SHORTINIT bootstrap, marketplace/vendor search, qTranslate-XT, per-form CSS textarea, hide-on-page rules, analytics CSV, a dedicated troubleshooting module, and first-party widgets for page builders other than Gutenberg.
