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

  $updated_blocks = [];
  foreach ($blocks as $block) {
    $settings = $block->get('settings');
    if (empty($settings['button']['label'])) {
      $settings['button']['label'] = t('Search');
      $block->set('settings', $settings);
      $block->save();
      $updated_blocks[] = $block->id();
    }
  }
  if (!empty($updated_blocks)) {
    return 'The following search blocks have been updated: ' . implode(', ', $updated_blocks);
  }
  return 'No search block needed to be updated.';
}

/**
 * Move the Whitelabel Search Block to the 'header_top' region.
 */
function oe_whitelabel_search_post_update_00003(): string {
  $message_append = "\nIf you are using a sub-theme of oe_whitelabel, you need to move the search block yourself.";
  $block = Block::load('oe_whitelabel_search_form');
  if (!$block || $block->getTheme() !== 'oe_whitelabel' || $block->getPluginId() !== 'whitelabel_search_block') {
    return 'No update needed.' . $message_append;
  }
  if ($block->getRegion() === 'header_top') {
    return 'Search block already in correct region.' . $message_append;
  }
  if ($block->getRegion() !== 'navigation_right') {
    return 'The search block was intentionally moved to a different region.' . $message_append;
  }

  $block->setRegion('header_top');
  $settings = $block->get('settings');
  $settings['form']['region'] = 'header_top';
  $block->set('settings', $settings);
  $block->save();

  return "Search block moved to 'header_top' for oe_whitelabel theme." . $message_append;
}
