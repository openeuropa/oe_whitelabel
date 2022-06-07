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
function oe_whitelabel_starter_news_post_update_00001(): void {
  $configs = [
    'core.entity_view_display.node.oe_sc_news.full',
    'core.entity_view_display.node.oe_sc_news.oe_w_content_banner',
  ];
  ConfigImporter::importMultiple('module', 'oe_whitelabel_starter_news', '/config/post_updates/00001_content_banner', $configs, TRUE);
}
