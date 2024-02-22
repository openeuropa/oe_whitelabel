<?php

/**
 * @file
 * OpenEuropa Whitelabel List Pages post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add the sort extra field to the list page content type.
 */
function oe_whitelabel_list_pages_post_update_00001() {
  ConfigImporter::importSingle('module', 'oe_whitelabel_list_pages', '/config/post_updates/00001_sort', 'core.entity_view_display.node.oe_list_page.default');
}

/**
 * Add content banner to list pages.
 */
function oe_whitelabel_list_pages_post_update_00002() {
  \Drupal::service('module_installer')->install(['oe_content_extra_list_pages']);

  ConfigImporter::importMultiple('module', 'oe_whitelabel_list_pages', '/config/post_updates/00002_content_banner', [
    'core.entity_view_display.node.oe_list_page.default',
    'core.entity_view_display.node.oe_list_page.full',
  ]);
}
