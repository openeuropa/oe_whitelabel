<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
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

/**
 * Place the OEL mega menu block.
 */
function oe_whitelabel_helper_post_update_00004(): string {
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return 'No blocks can be placed, because the block module is not installed.';
  }

  ConfigImporter::importSingle('module', 'oe_whitelabel_helper', '/config/post_updates/00004_megamenu', 'block.block.oe_whitelabel_oelmegamenu');

  $report = 'The new mega menu block was placed in the navigation region for oe_whitelabel.';

  $old_navigation_block = Block::load('oe_whitelabel_main_navigation');
  if (!$old_navigation_block || $old_navigation_block->getTheme() !== 'oe_whitelabel') {
    return $report . "\nThe old navigation block was not found.";
  }
  if (!$old_navigation_block->status()) {
    return $report . "\nThe old navigation block was already disabled.";
  }

  $old_navigation_block->setStatus(FALSE)->save();

  return $report . "\nThe old navigation block was disabled.";
}

/**
 * Apply the no-crop image style to gallery thumbnails.
 */
function oe_whitelabel_helper_post_update_00005(): void {
  $bundles = [
    'av_portal_photo',
    'av_portal_video',
    'image',
    'remote_video',
  ];

  foreach ($bundles as $bundle) {
    $display = EntityViewDisplay::load("media.$bundle.oe_w_pattern_gallery_item");
    if (!$display || !$component = $display->getComponent('thumbnail')) {
      continue;
    }
    if (!empty($component['settings']['image_style'])) {
      continue;
    }

    $component['settings']['image_style'] = 'oe_bootstrap_theme_medium_no_crop';
    $display->setComponent('thumbnail', $component)->save();
  }
}
