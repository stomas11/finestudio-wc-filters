=== WC Auto Product Filters ===
Contributors: finestudio
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: finestudio-wc-filters

Advanced WooCommerce product filters with shortcode, category-aware context, AJAX/non-AJAX mode, color swatches, dynamic counts and admin controls.

== Description ==

WC Auto Product Filters adds configurable WooCommerce filters with shortcode output and admin UI.

Main goals:
- show relevant filters for current context (shop/category),
- allow custom display type per filter,
- support auto-submit or button submit,
- support optional AJAX updates,
- support color attributes with swatches,
- support dynamic option counts and disabled states.

== Features ==

### Frontend filters
- Price (range slider with min/max inputs)
- Availability (`In stock only`)
- On sale (`On sale only`)
- Product attributes (`pa_*`) with configurable display type:
  - `checkbox`
  - `radio`
  - `select`
  - `multiselect`
  - `swatches`

### Context and category behavior
- Auto context detection for shop/category pages.
- Category context detection supports:
  - native product category archive (`is_product_category()`),
  - `product_cat` query var fallback.
- Attribute filters are restricted to category-relevant ones.
- Per-category overrides in admin:
  - enabled/disabled filter,
  - custom order,
  - custom label,
  - custom display type.

### Color attributes and swatches
- Manual color attribute assignment in Settings:
  - `Color attributes (taxonomy keys, comma separated)`
- Attribute Colors page is shown only for configured color taxonomies.
- Behavior:
  - if filter is configured as color and display type is `swatches`: color squares,
  - if display type is `swatches` but filter is not color: text pill swatches.
- For color filters rendered as checkbox, inline color square is shown before term label.

### Dynamic counts and disabled options
- Option counts are rendered next to labels (checkbox, radio, text swatches).
- Counts are recalculated dynamically for current context/filter state.
- For facet-style behavior, counts for a taxonomy are calculated while ignoring that taxonomy's own selected filter (self-exclusion).
- Options with `0` results are styled as disabled (grey + non-clickable), except already selected options remain clickable so they can be unselected.

### Layout and panel UX
- Filters layout:
  - `stacked`
  - `columns` (desktop column count configurable)
- Optional collapse of visible filters + `Show all filters` button.
- Optional sidebar panel mode (desktop + mobile).
- Per-field "Show more options" behavior for long option lists.
- Close button and panel action area behavior tuned for mobile/desktop.

### AJAX and non-AJAX
- Works with AJAX enabled or disabled.
- Clean query generation:
  - ignores empty filter params,
  - ignores default price min/max when unchanged.
- Products list, pagination and result-count sections are updated on AJAX refresh.

== Shortcode ==

Use:
`[fs_product_filters]`

Optional attributes:
- `context="auto|category"` (default: `auto`)
- `category="slug-or-id"` (used with `context="category"`)

== Admin pages ==

1. Product Filters (overview):
- global filter order
- enabled
- label
- display type
- category overrides (enabled/order/label/type)

2. Attribute Colors:
- per-term hex colors for configured color attributes

3. Settings:
- enable AJAX
- auto submit
- update browser URL
- submit mode (`auto` / `button`)
- primary color
- products selector / container ID
- color attributes list
- filters layout and desktop columns
- collapse/show all behavior
- sidebar panel mode
- visible filters count

== Known behavior notes ==

- Product with multiple attribute values can contribute to multiple facet values.
- Counts are product-based (parent product level), not strict variation-row level.
- Result count text can vary by theme markup; plugin attempts synchronization after AJAX update.

== Performance notes ==

For large catalogs:
- enable object cache (Redis/Memcached),
- avoid heavy full catalog scans when possible,
- consider custom precomputed facet index table for maximum speed.

== Changelog ==

= 0.1.0 =
- Initial public plugin version.
- Shortcode frontend filters (price/stock/sale/attributes).
- Admin configuration and category overrides.
- Color attributes + swatches support.
- Optional AJAX refresh and clean URL handling.
- Dynamic counts and disabled options.

