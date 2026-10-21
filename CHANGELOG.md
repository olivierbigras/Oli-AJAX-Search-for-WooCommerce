# Changelog

## 0.2.1

- Escape widget wrappers, admin class names, and shortcode HTML with `wp_kses_post` / `esc_attr`.
- Extract the Menus-screen inline script to `assets/js/admin-nav-menu.js` and enqueue it with `wp_localize_script`.
- Add `readme.txt` **External services** (no default remote calls; custom preloader URL is opt-in).
- Upgrade migrates remapped options onto the current `oli_as_` keys and deletes the old keys.

## 0.2.0

- Settings tabs now match the original premium order: Starting, Search bar, Autocomplete, Search config, Analytics, Indexer, Troubleshooting.
- Field labels, help text, and grouping follow that reference (Oli-branded style names: Oval, Oval compact, Rectangle).
- Four layouts: search bar, icon, icon on mobile, icon on desktop.
- Public search endpoints are rate-limited by hashed IP. Private, draft, catalog-only, and password-protected products stay out of suggestions.
- Frontend CSS/JS load only when a form is rendered or theme replacement is enabled.
- Query cache, CSV analytics export, scheduler interval + start time, sale/featured badges, Elementor widget, navigation block, qTranslate-XT.
- Complete Canadian French (`fr_CA`) translations: `.po`, `.mo`, and `.l10n.php`.
- CI: PHP 7.4–8.4, PHPCS, PHPUnit, Plugin Check (slug `oli-ajax-search`, 0 errors), ZIP artifact with MD5.

## 0.1.1

- TranslatePress language slugs, HTML descriptions, and results-page query protection.
- Fuzzy levels, GTIN search, brands group, include/exclude IDs, daily rebuild.
- Frontend highlight, darkened backdrop, preloader, mobile breakpoint, max width.

## 0.1.0

- First independent GPL release: custom index, shortcode, widget, block, menu, theme replace, local analytics.
