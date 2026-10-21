# Correspondence: original premium plugin → Oli AJAX Search 0.2.0

Status: **Done** (same behavior), **Improved** (same screen plus an Oli addition), **Absent** (not shipped).

The original package is used only as a private behavioral reference. This repository contains original GPL code. Vendor names, logos, license keys, and remote licensing calls are not present.

## Admin screens

| Original screen | Oli screen | Status | Notes |
| --- | --- | --- | --- |
| Settings → Starting | WooCommerce → Oli AJAX Search → Starting | Done | Theme replace + five embedding methods, Oli-branded |
| Settings → Search bar | Search bar | Done | Same field order and labels |
| Settings → Autocomplete | Autocomplete | Done | Same groups and extra views |
| Settings → Search config | Search config | Done | Scope, fuzzy, synonyms, include/exclude |
| Settings → Marketplace | — | Absent | No vendor marketplace module |
| Settings → Analytics | Analytics | Improved | Local only, plus CSV export |
| Settings → Indexer | Indexer | Done | Status, rebuild, scheduler interval + time |
| Settings → Troubleshooting | Troubleshooting | Done | Local checks, no remote pings |
| Debug submenu | — | Absent | Not shipped (debug was vendor-oriented) |
| License account / contact screens | — | Absent | Intentionally omitted |
| Live search preview on settings | Preview on Search bar / Autocomplete | Done | |
| Show advanced settings | Same toggle | Done | |

## Search bar fields

| Original field | Oli | Status |
| --- | --- | --- |
| Minimum characters | Same label | Done |
| Max form width | Same label | Done |
| Show submit button | Same label | Done |
| Label (submit) | Same label + magnifier help | Done |
| Background color / Text color | Same labels | Done |
| Search input placeholder | Same label | Done |
| Style Pirx / Pirx compact / Solaris | Oval / Oval compact / Rectangle | Improved | Visual equivalents, Oli names |
| Layout: bar / icon / flexible / inverse | Same four layouts | Done |
| Breakpoint | Same label | Done |
| Overlay on mobile + breakpoint | Same labels | Done |
| Darkened background | Same label and help | Done |
| Search bar underlay / input colors | Same labels | Done |
| Show preloader | Same label | Done |
| Upload preloader image | Same label | Done |
| Voice search | — | Absent |

## Autocomplete fields

| Original field | Oli | Status |
| --- | --- | --- |
| Limit | Same label | Done |
| No results label (HTML allowed) | Same | Done |
| Show product image / price / description / SKU | Same | Done |
| Sale / featured badges | Present (were commented in original) | Improved |
| More results label | Same | Done |
| Show categories + show images | Same | Done |
| Show tags / posts / pages / brands | Same | Done |
| Show Details Panel | Same help | Done |
| User search history | Same help | Done |
| Suggestion colors | Same five colors | Done |
| Stock status | Extra Oli field | Improved |

## Search config fields

| Original field | Oli | Status |
| --- | --- | --- |
| Search in description / short description | Same | Done |
| Search in SKU (variations) | Same | Done |
| Search in GUID (GTIN/UPC/EAN/ISBN) | Same | Done |
| Search in attributes / categories / tags | Same | Done |
| Search in custom fields | Textarea of keys | Done |
| Exclude out of stock | Same | Done |
| Fuzzy matching off/soft/normal/hard | Same | Done |
| Synonyms (comma, one group per line) | Same sample help | Done |
| Filtering mode Exclude/Include | Same | Done |
| Filters (IDs + on sale / featured) | Simplified rule builder | Improved |
| Stopwords | Extra Oli field | Improved |
| Relevance weights | Extra Oli field | Improved |

## Front-end

| Original behavior | Oli | Status |
| --- | --- | --- |
| Search bar + suggestions | Done | Own CSS/JS |
| Hover details + add to cart | Done | |
| Full-screen mobile overlay | Done | |
| Recent searches (localStorage) | Done | |
| Highlight matched terms | Done | |
| Theme replace | Storefront, Astra, Kadence, GeneratePress, OceanWP, Blocksy + JS selectors | Improved |
| Shortcode | `[oli_search]` / `[oli_ajax_search]` | Done |
| Widget | Oli AJAX Search bar | Done |
| Gutenberg block | Search form + navigation item | Done |
| Menu item | Done | |
| Elementor widget | Done | |
| Bricks / Brizy widgets | — | Absent |
| Results page same relevance | Done | |

## Engine

| Original behavior | Oli | Status |
| --- | --- | --- |
| Custom inverted index | Done | Own tables `oli_as_*` |
| Incremental product/stock/term updates | Done | |
| Scheduled rebuild (daily/weekly + hour) | Done | |
| Query cache | Versioned transients | Improved |
| Synonyms | Done | |
| Fuzzy levels | Done | |
| Variation SKU exact match | Done | |
| Hide private / draft / catalog-only / password | Done | Extra safety on hydrate |
| Public endpoint rate-limited by IP | Done | Improvement |
| SHORTINIT standalone PHP endpoint | Pretty `/oli-as/suggest` + REST + AJAX | Improved (not SHORTINIT) |
| 100k+ debugger / failure emails | — | Absent |

## Multilingual

| Original | Oli | Status |
| --- | --- | --- |
| TranslatePress | Done | Slugs, HTML descriptions, skip dynamic rewrite, results-page query |
| WPML | Done | |
| Polylang | Done | |
| qTranslate-XT | Done | |

## Analytics / licensing

| Original | Oli | Status |
| --- | --- | --- |
| Local analytics | Done | |
| Dashboard critical-search widget | Done | |
| CSV export | Done | |
| Remote licensing / vendor servers | — | Absent by design |

## Assets and security

| Requirement | Status |
| --- | --- |
| JS/CSS only when a form is present or theme replace is on | Done |
| Admin nonces + `manage_woocommerce` | Done |
| Escaping / prepared SQL | Done |
| Public search IP rate limit | Done |
| No private/draft leakage | Done |
