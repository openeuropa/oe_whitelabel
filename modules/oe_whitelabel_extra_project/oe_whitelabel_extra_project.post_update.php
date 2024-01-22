<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types =  1);

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
  ConfigImporter::importSingle('module', 'oe_whitelabel_extra_project', '/config/post_updates/00002_field_group', 'core.entity_view_display.node.oe_project.oe_w_content_banner');
}
