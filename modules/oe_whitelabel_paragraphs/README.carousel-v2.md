# Carousel V2

`oe_whitelabel_paragraphs` installs `oe_carousel_v2`, reuses the existing
`oe_carousel_item` bundle and `field_oe_carousel_items` storage, and applies the
existing `CarouselItemsCardinality` constraint (at least two items).
The required `oe_w_carousel_layout` field accepts `split` (default) and
`full_width`. V1 configuration, content and templates are unchanged; both versions
share slide preparation while preserving V1's output.
Existing installations receive the new configuration through post-update 00004.
Sites restricting allowed paragraph bundles must explicitly allow `oe_carousel_v2`.

## OE Bootstrap Theme integration contract

The template includes `oe_bootstrap_theme:carousel_v2`, matching the SDC on OE BT's
`OEL-5004` branch (verified 2026-09-17). The installed OE BT dependency must contain
that component. No fallback to V1 or bundled substitute SDC is supplied here.

Expected inputs:

- `layout`: `split` or `full_width`.
- `attributes`: Drupal paragraph attributes; the component must preserve these.
- `items`: an array containing `caption_title`, an escaped paragraph of `caption`
  markup, `image` (`src`, `alt`, optional string `width` and `height`), optional `copyright`,
  and optional `link` (`path`, `label`, Drupal `attributes`).

OE BT owns the SDC schema, BCL mapping, translated control/role labels, icon
paths, unique carousel IDs, and asset attachment/JavaScript initialization.
Whitelabel resolves entity translations and media/link access and propagates
paragraph, item, media, file, URL and access-result cache metadata.
Image alt text comes from the translated image field, including intentionally
empty alt text; AV Portal photos use their translated media label.

Rendering tests require the actual OE BT Carousel V2 SDC. Configuration and
preprocess tests can run before that dependency is available.
