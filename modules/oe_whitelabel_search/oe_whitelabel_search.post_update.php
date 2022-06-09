<?php

/**
 * @file
 * OpenEuropa Whitelabel Search post updates.
 */

declare(strict_types=1);

use Drupal\block\Entity\Block;

/**
 * Add region in form settings for template suggestions.
 */
function oe_whitelabel_search_post_update_00001(&$sandbox) {
  $block = Block::load('oe_whitelabel_search_form');
  $settings = $block->get('settings');
  $settings['form']['region'] = $block->getRegion();
  $block->set('settings', $settings);
  $block->save();
}
