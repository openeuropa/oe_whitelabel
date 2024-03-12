<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Move content banner into a block.
 */
function oe_whitelabel_starter_news_post_update_00001(): void {
  $configs = [
    'core.entity_view_display.node.oe_sc_news.full',
    'core.entity_view_display.node.oe_sc_news.oe_w_content_banner',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_starter_news', '/config/post_updates/00001_content_banner', $configs, TRUE);
}

/**
 * Update the content banner news view mode to introduce field group.
 */
function oe_whitelabel_starter_news_post_update_00002(): void {
  \Drupal::service('module_installer')->install(['field_group']);
  $default_settings = \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings('html_element', 'view');

  $field_group = [
    'children' => [],
    'parent_name' => '',
    'label' => 'Action bar',
    'format_type' => 'html_element',
    'format_settings' => $default_settings,
    'region' => 'content',
    'weight' => 2,
  ];

  $display = EntityViewDisplay::load('node.oe_sc_news.oe_w_content_banner');

  // If no display was found, we bail out.
  if (!isset($display)) {
    return;
  }

  $display->setThirdPartySetting('field_group', 'group_action_bar', $field_group);
  $display->save();
}
