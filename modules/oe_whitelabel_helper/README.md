# OpenEuropa Whitelabel Helper

This module offers some additional functionality that might come in useful when
theming an OpenEuropa website.

### Twig helpers
#### bcl_timeago filter
Filters a timestamp in "time ago" format, result can be something like "8 hours ago".
```
node.getCreatedTime|bcl_timeago
```
#### bcl_footer_links function
Processes oe_corporate_blocks links to make them compatible with BCL formatting.
```
bcl_footer_links(links)
```
#### bcl_block function
Builds the render array for a block.
```
bcl_block(block)
```
