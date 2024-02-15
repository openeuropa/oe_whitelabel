<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Move content banner into a block.
 */
function oe_whitelabel_helper_post_update_00001(): void {
  $configs = [
    'block.block.oe_whitelabel_content_banner',
    'core.entity_view_mode.node.oe_w_content_banner',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_helper', '/config/post_updates/00001_content_banner', $configs, TRUE);
}

/**
 * Create date range formats.
 */
function oe_whitelabel_helper_post_update_00002(): void {
  \Drupal::service('module_installer')->install(['daterange_compact']);

  $configs = [
    'daterange_compact.format.oe_whitelabel_date_only_short_month',
    'daterange_compact.format.oe_whitelabel_date_time_long',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_helper', '/config/post_updates/00002_date_range_formats', $configs, TRUE);
}

/**
 * Install the gallery item view mode if dependencies are met.
 */
function oe_whitelabel_helper_post_update_00003(): void {
  $storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('oe_whitelabel_helper') . '/config/post_updates/00003_gallery_formatter');
  \Drupal::service('config.installer')->installOptionalConfig($storage);
}
