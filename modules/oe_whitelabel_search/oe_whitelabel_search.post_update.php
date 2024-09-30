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

/**
 * Set default button label in search block if it's not already set.
 */
function oe_whitelabel_search_post_update_00002(&$sandbox) {
  $theme_manager = \Drupal::service('theme.manager');
  $current_theme = $theme_manager->getActiveTheme()->getName();

  $block_storage = \Drupal::entityTypeManager()->getStorage('block');
  $blocks = $block_storage->loadByProperties(['theme' => $current_theme, 'plugin' => 'whitelabel_search_block']);

  foreach ($blocks as $block) {
    $settings = $block->get('settings');
    if (empty($settings['button']['label'])) {
      $settings['button']['label'] = t('Search');
      $block->set('settings', $settings);
      $block->save();
    }
  }
}
