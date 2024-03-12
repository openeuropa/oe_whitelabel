<?php

/**
 * @file
 * Contains install, update and uninstall functions.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Update the content banner person view mode to introduce field group.
 */
function oe_whitelabel_starter_person_post_update_00001(): string {
  \Drupal::service('module_installer')->install(['field_group']);
  $default_settings = \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings('html_element', 'view');

  $field_group = [
    'children' => [],
    'parent_name' => '',
    'label' => 'Action bar',
    'format_type' => 'html_element',
    'format_settings' => $default_settings,
    'region' => 'content',
    'weight' => 4,
  ];

  $display = EntityViewDisplay::load('node.oe_sc_person.oe_w_content_banner');

  // If no display was found, we bail out.
  if (!isset($display)) {
    return 'Content banner view display not found for person content type.';
  }
  // If there is a group_action_bar, we bail out.
  if ($display->getThirdPartySetting('field_group', 'group_action_bar')) {
    return 'Action bar field group already exists for person content banner view display.';
  }

  $display->setThirdPartySetting('field_group', 'group_action_bar', $field_group);
  $display->save();

  return 'Action bar field group was added to the person content banner view display.';
}
