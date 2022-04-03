# OpenEuropa Whitelabel oe_list_page test

This module provides an environment to test manually oe_whitelabel_list_pages.

## Requirements

- Add this line to an override settings.php file:
```json
$settings['extension_discovery_scan_tests'] = TRUE;
```
- Enable this module.
- Create some oe_sc_news content.
- Create a new List Page and configure:
  - Title: "News"
  - Source entity type: "Content"
  - Source bundle: News
  - Override default exposed filters: enabled
  - Exposed filters: Enable "Title"
- Save
