<?php

/**
 * @file
 * OpenEuropa Whitelabel List Pages post updates.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Add the sort extra field to the list page content type.
 */
function oe_whitelabel_list_pages_post_update_00001() {
  ConfigImporter::importSingle('module', 'oe_whitelabel_list_pages', '/config/post_updates/00001_sort', 'core.entity_view_display.node.oe_list_page.default');
}

/**
 * Configure displays for the node fields migrated by OE List Pages V2.
 */
function oe_whitelabel_list_pages_post_update_00002(): void {
  $form_display = EntityFormDisplay::load('node.oe_list_page.default');
  if ($form_display) {
    $form_display->setComponent('oe_list_page_config', [
      'type' => 'oe_list_pages_configuration',
      'weight' => 1,
    ]);
    $form_display->removeComponent('oe_list_page_source');
    $form_display->save();
  }

  $view_displays = \Drupal::entityTypeManager()->getStorage('entity_view_display')->loadByProperties([
    'targetEntityType' => 'node',
    'bundle' => 'oe_list_page',
  ]);
  foreach ($view_displays as $view_display) {
    $view_display->removeComponent('oe_list_page_config');
    $view_display->removeComponent('oe_list_page_source');
    $view_display->save();
  }
}
