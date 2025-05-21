<?php

/**
 * @file
 * Post-update functions for the OE Whitelabel Paragraphs module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

/**
 * Migrate allowed_formats from contrib (third-party) to core settings.
 */
function oe_whitelabel_paragraphs_post_update_00001(array &$sandbox): void {
  // 1. Fields that used allowed_formats.
  $field_ids = [
    'paragraph.oe_list_item.field_oe_text_long',
  ];

  foreach ($field_ids as $id) {
    if (($field = FieldConfig::load($id))) {
      // Pull the old value, if any, from the third-party setting.
      $old_formats = $field->getThirdPartySetting('allowed_formats', 'allowed_formats', []);
      if (!empty($old_formats)) {
        // Write it to the new core location.
        $settings = $field->get('settings');
        $settings['allowed_formats'] = $old_formats;
        $field->set('settings', $settings);

        // Remove obsolete key.
        $field->unsetThirdPartySetting('allowed_formats', 'allowed_formats');
        $field->save();
      }
    }
  }

  // 2. Clean up entity-form-displays so they no longer reference the key.
  //    Keyed by display ID => component (field) name.
  $form_displays = [
    'paragraph.oe_list_item.default' => 'field_oe_text_long',
  ];

  foreach ($form_displays as $display_id => $component_name) {
    if (($display = EntityFormDisplay::load($display_id))) {
      if ($component = $display->getComponent($component_name)) {
        unset($component['third_party_settings']['allowed_formats']);
        $display->setComponent($component_name, $component);
        $display->save();
      }
    }
  }

}
