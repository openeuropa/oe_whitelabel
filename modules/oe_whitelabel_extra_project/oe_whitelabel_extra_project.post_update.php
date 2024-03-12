<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Swap deprecated budget and eu contribution for new decimal fields.
 */
function oe_whitelabel_extra_project_post_update_00001(): void {
  $configs = [
    'core.entity_form_display.node.oe_project.default',
    'core.entity_view_display.node.oe_project.full',
    'core.entity_view_display.node.oe_project.oe_w_content_banner',
    'core.entity_view_display.node.oe_project.teaser',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_extra_project', '/config/post_updates/00001_decimal_budget_fields', $configs, TRUE);
}

/**
 * Update the content banner project view mode to introduce field group.
 */
function oe_whitelabel_extra_project_post_update_00002(): void {
  \Drupal::service('module_installer')->install(['field_group']);
  \Drupal::moduleHandler()->loadInclude('field_group', 'module');
  $default_settings = \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings('html_element', 'view');

  $field_group = (object) [
    'group_name' => 'group_action_bar',
    'entity_type' => 'node',
    'bundle' => 'oe_project',
    'mode' => 'oe_w_content_banner',
    'context' => 'view',
    'children' => [],
    'parent_name' => '',
    'label' => 'Action bar',
    'format_type' => 'html_element',
    'format_settings' => $default_settings,
    'region' => 'content',
    'weight' => 20,
  ];

  $display = EntityViewDisplay::load($field_group->entity_type . '.' . $field_group->bundle . '.' . $field_group->mode);

  // If no display was found, we bail out.
  if (!isset($display)) {
    return;
  }

  field_group_group_save($field_group, $display);
}
