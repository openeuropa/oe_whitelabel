<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types =  1);

use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Move content banner into a block.
 */
function oe_whitelabel_helper_post_update_00001(): void {
  $configs = [
    'block.block.oe_whitelabel_content_banner',
    'core.entity_view_mode.node.oe_w_content_banner',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_helper', '/config/post_updates/00001_content_banner', $configs, TRUE);
}
