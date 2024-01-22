<?php

/**
 * @file
 * Contains install, update and uninstall functions.
 */

declare(strict_types = 1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Update the content banner publication view mode to introduce field group.
 */
function oe_whitelabel_starter_publication_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['field_group']);
  ConfigImporter::importSingle('module', 'oe_whitelabel_starter_publication', '/config/post_updates/00001_field_group', 'core.entity_view_display.node.oe_sc_publication.oe_w_content_banner');
}
