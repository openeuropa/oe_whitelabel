# Carousel V2

`oe_whitelabel_paragraphs` installs `oe_carousel_v2`, reuses the existing
`oe_carousel_item` bundle and `field_oe_carousel_items` storage, and applies the
existing `CarouselItemsCardinality` constraint (at least two items).
The boolean `oe_w_carousel_hero` field is shown as a "Use Carousel hero"
checkbox. Unchecked (default) renders Carousel image (`split`); checked renders
Carousel hero (`full_width`). No migration from the former dropdown is provided.
The independent "Span page width" checkbox (`oe_w_carousel_full_bleed`) defaults
to off. It lets either layout span the page from inside `.container`.
Slide titles use the component default `p` tag and retain their h1-sized display
style. There is no heading-level selector in the paragraph form.
V1 configuration, content and templates are unchanged; both versions
share slide preparation while preserving V1's output.
Fresh installations receive the fields and displays through install configuration.
Existing installations receive Carousel V2, including “Span page width”, through
post-update 00004. No separate 00005 update is needed.
Sites restricting allowed paragraph bundles must explicitly allow `oe_carousel_v2`.

## OE Bootstrap Theme integration contract

The template includes `oe_bootstrap_theme:carousel_v2`, matching the SDC on OE BT's
prerelease `0.5004.202610151400`. The installed OE BT dependency must contain
that component. No fallback to V1 or bundled substitute SDC is supplied here.

Expected inputs:

- `layout`: `split` or `full_width`.
- `full_bleed`: independent boolean for page-wide rendering; defaults to false.
- `attributes`: Drupal paragraph attributes; the component must preserve these.
- `items`: an array containing `caption_title`, an escaped paragraph of `caption`
  markup, `image` (`src`, `alt`, optional string `width` and `height`), optional `copyright`,
  and optional `link` (`path`, `label`, optional plain attribute map).
  OE BT must normalize link attributes with `create_attribute()` before passing
  them to BCL. Empty link attributes are omitted.

OE BT owns the SDC schema, BCL mapping, translated control/role labels, icon
paths, unique carousel IDs, and asset attachment/JavaScript initialization.
Whitelabel resolves entity translations and media/link access and propagates
paragraph, item, media, file, URL and access-result cache metadata.
Image alt text comes from the translated image field, including intentionally
empty alt text; AV Portal photos use their translated media label.

Rendering tests require the actual OE BT Carousel V2 SDC. Configuration and
preprocess tests can run before that dependency is available.

## Playback

Autoplay defaults to on with a 10-second interval and an 8-second minimum.
BCL extends longer slides using `3 seconds + 0.4 seconds per word`.
Mouse hover and focus inside the carousel do not pause rotation. Pause freezes
the visible 3px progress bar and preserves remaining time; Play resumes both.
Reduced motion starts paused and hides the progress animation. These behaviors
come from OE Bootstrap Theme's BCL assets, without another Whitelabel handler.
