<?php

/**
 * @file
 * OpenEuropa Showcase post updates.
 */

declare(strict_types=1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Set the updated values to the search form block.
 */
function oe_whitelabel_search_post_update_00001(&$sandbox) {
  $configs = [
    'block.block.oe_whitelabel_search_form',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_search', '/config/post_updates/00001', $configs);
}
