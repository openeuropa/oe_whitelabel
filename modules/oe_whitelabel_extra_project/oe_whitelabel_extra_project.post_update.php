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
 * Enable the country flag rendering in project organisations.
 */
function oe_whitelabel_extra_project_post_update_00002(&$sandbox) {
  $view_display_ids = [
    'oe_organisation.oe_cx_project_stakeholder.default',
    'oe_organisation.oe_stakeholder.default',
  ];

  /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay[] $view_displays */
  $view_displays = \Drupal::entityTypeManager()->getStorage('entity_view_display')->loadMultiple($view_display_ids);
  foreach ($view_displays as $view_display) {
    $component = $view_display->getComponent('oe_address');
    if ($component === NULL) {
      continue;
    }

    $component['settings']['show_country_flag'] = TRUE;
    $view_display->setComponent('oe_address', $component)->save();
  }
}
