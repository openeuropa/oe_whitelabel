<?php

/**
 * @file
 * Post update functions for the OE Whitelabel Paragraphs module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;

/**
 * Set the 'allowed_formats' for text fields and update entity displays.
 */
function oe_whitelabel_paragraphs_post_update_00001(array &$sandbox): void {
  // Define the fields that need their 'allowed_formats' set.
  $field_ids = [
    'paragraph.oe_list_item.field_oe_text_long',
    'paragraph.oe_illustration_item_flag.field_oe_text_long',
    'paragraph.oe_illustration_item_icon.field_oe_text_long',
    'paragraph.oe_illustration_item_image.field_oe_text_long',
    'paragraph.oe_text_feature_media.field_oe_text_long',
    'paragraph.oe_timeline.field_oe_text_long',
  ];

  foreach ($field_ids as $field_id) {
    $field_config = FieldConfig::load($field_id);
    if ($field_config) {
      // Set the 'allowed_formats' directly in the field settings.
      $settings = $field_config->get('settings');
      $settings['allowed_formats'] = ['basic_html'];
      $field_config->set('settings', $settings);
      $field_config->save();
    }
  }

  // Define the form and view displays to be updated.
  $displays = [
    'paragraph.oe_list_item.default',
    'paragraph.oe_illustration_item_flag.default',
    'paragraph.oe_illustration_item_icon.default',
    'paragraph.oe_illustration_item_image.default',
    'paragraph.oe_text_feature_media.default',
    'paragraph.oe_timeline.default',
  ];

  foreach ($displays as $display_id) {
    // Load and update the entity form display.
    $form_display = EntityFormDisplay::load($display_id);
    if ($form_display) {
      $form_display->save();
    }

    // Load and update the entity view display.
    $view_display = EntityViewDisplay::load($display_id);
    if ($view_display) {
      $view_display->save();
    }
  }
}
