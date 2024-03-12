<?php

/**
 * @file
 * Contains install, update and uninstall functions.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Update the content banner publication view mode to introduce field group.
 */
function oe_whitelabel_starter_publication_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['field_group']);
  \Drupal::moduleHandler()->loadInclude('field_group', 'module');
  $default_settings = \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings('html_element', 'view');

  $field_group = (object) [
    'group_name' => 'group_action_bar',
    'entity_type' => 'node',
    'bundle' => 'oe_sc_publication',
    'mode' => 'oe_w_content_banner',
    'context' => 'view',
    'children' => [],
    'parent_name' => '',
    'label' => 'Action bar',
    'format_type' => 'html_element',
    'format_settings' => $default_settings,
    'region' => 'content',
    'weight' => 2,
  ];

  $display = EntityViewDisplay::load($field_group->entity_type . '.' . $field_group->bundle . '.' . $field_group->mode);

  // If no display was found, we bail out.
  if (!isset($display)) {
    return;
  }

  field_group_group_save($field_group, $display);
}
