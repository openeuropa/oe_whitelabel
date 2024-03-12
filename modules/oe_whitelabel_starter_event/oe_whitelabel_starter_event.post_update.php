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
function oe_whitelabel_starter_event_post_update_00001(): void {
  $configs = [
    'core.entity_view_display.node.oe_sc_event.full',
    'core.entity_view_display.node.oe_sc_event.oe_w_content_banner',
    'core.entity_view_display.node.oe_sc_event.teaser',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_starter_event', '/config/post_updates/00001_content_banner', $configs, TRUE);
}

/**
 * Add time to event dates.
 */
function oe_whitelabel_starter_event_post_update_00002(): void {
  $configs = [
    'core.entity_view_display.node.oe_sc_event.full',
    'core.entity_view_display.node.oe_sc_event.teaser',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_starter_event', '/config/post_updates/00002_event_date_show_time', $configs);
}

/**
 * Add registration URL field to event content_banner view.
 */
function oe_whitelabel_starter_event_post_update_00003(): void {
  $configs = [
    'core.entity_view_display.node.oe_sc_event.full',
    'core.entity_view_display.node.oe_sc_event.oe_w_content_banner',
    'core.entity_view_display.node.oe_sc_event.teaser',
  ];
  ConfigImporter::importMultiple(
    'module',
    'oe_whitelabel_starter_event',
    '/config/post_updates/00003_add_registration_url_field',
    $configs,
    TRUE
  );
}

/**
 * Add location to Event teaser.
 */
function oe_whitelabel_starter_event_post_update_00004(): void {
  ConfigImporter::importSingle('module', 'oe_whitelabel_starter_event', '/config/post_updates/00004_teaser_location', 'core.entity_view_display.node.oe_sc_event.teaser');
}

/**
 * Update the content banner event view mode to introduce field group.
 */
function oe_whitelabel_starter_event_post_update_00005(): void {
  \Drupal::service('module_installer')->install(['field_group']);
  \Drupal::moduleHandler()->loadInclude('field_group', 'module');
  $default_settings = \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings('html_element', 'view');

  $field_group = (object) [
    'group_name' => 'group_action_bar',
    'entity_type' => 'node',
    'bundle' => 'oe_sc_event',
    'mode' => 'oe_w_content_banner',
    'context' => 'view',
    'children' => [],
    'parent_name' => '',
    'label' => 'Action bar',
    'format_type' => 'html_element',
    'format_settings' => $default_settings,
    'region' => 'content',
    'weight' => 1,
  ];

  $display = EntityViewDisplay::load($field_group->entity_type . '.' . $field_group->bundle . '.' . $field_group->mode);

  // If no display was found, we bail out.
  if (!isset($display)) {
    return;
  }

  field_group_group_save($field_group, $display);
}
