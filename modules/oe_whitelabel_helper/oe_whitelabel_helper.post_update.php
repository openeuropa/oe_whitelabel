<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;
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
 * Maps media copyright field to gallery copyright where available.
 */
function oe_whitelabel_helper_post_update_00005(): string {
  $field_name = 'field_media_copyright';
  $field_manager = \Drupal::service('entity_field.manager');
  $display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');

  $updated = [];
  foreach (['image', 'av_portal_photo'] as $bundle) {
    $field_definitions = $field_manager->getFieldDefinitions('media', $bundle);
    if (!isset($field_definitions[$field_name])) {
      continue;
    }

    $display_id = "media.$bundle.oe_w_pattern_gallery_item";
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
    $display = $display_storage->load($display_id);
    if (!$display) {
      continue;
    }

    $component = $display->getComponent($field_name) ?: [
      'type' => 'string',
      'label' => 'visually_hidden',
      'settings' => ['link_to_entity' => FALSE],
      'third_party_settings' => [],
      'weight' => 3,
      'region' => 'content',
    ];

    $third_party_settings = is_array($component['third_party_settings'] ?? NULL)
      ? $component['third_party_settings']
      : [];
    $current_mapping = $third_party_settings['oe_whitelabel_helper']['pattern_mapping'] ?? NULL;
    if ($current_mapping === 'copyright') {
      continue;
    }
    $third_party_settings['oe_whitelabel_helper']['pattern_mapping'] = 'copyright';
    $component['third_party_settings'] = $third_party_settings;

    $display->setComponent($field_name, $component)->save();
    $updated[] = $display_id;
  }

  if (empty($updated)) {
    return 'No gallery display was updated for media copyright mapping.';
  }

  return sprintf('Updated gallery copyright mapping on: %s.', implode(', ', $updated));
}
