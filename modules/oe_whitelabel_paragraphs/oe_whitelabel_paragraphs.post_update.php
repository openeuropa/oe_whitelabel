<?php

/**
 * @file
 * Post-update functions for the OE Whitelabel Paragraphs module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\oe_bootstrap_theme\ConfigImporter;

/**
 * Adds Carousel V2 without modifying existing Carousel paragraphs or displays.
 */
function oe_whitelabel_paragraphs_post_update_00004(): void {
  if (!\Drupal::moduleHandler()->moduleExists('oe_paragraphs_carousel')) {
    \Drupal::service('module_installer')->install(['oe_paragraphs_carousel']);
  }
  $configs = [
    'paragraphs.paragraphs_type.oe_carousel_v2',
    'field.storage.paragraph.oe_w_carousel_layout',
    'field.field.paragraph.oe_carousel_v2.oe_w_carousel_layout',
    'field.field.paragraph.oe_carousel_v2.field_oe_carousel_items',
    'core.entity_form_display.paragraph.oe_carousel_v2.default',
    'core.entity_view_display.paragraph.oe_carousel_v2.default',
  ];
  foreach ($configs as $name) {
    if (\Drupal::configFactory()->get($name)->isNew()) {
      ConfigImporter::importSingle('module', 'oe_whitelabel_paragraphs', '/config/install/', $name);
    }
  }
  $translation = 'language.content_settings.paragraph.oe_carousel_v2';
  if (\Drupal::moduleHandler()->moduleExists('content_translation') && \Drupal::configFactory()->get($translation)->isNew()) {
    ConfigImporter::importSingle('module', 'oe_whitelabel_paragraphs', '/config/optional/', $translation);
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

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

/**
 * Creates dedicated listing image copyright field and updates form display.
 */
function oe_whitelabel_paragraphs_post_update_00002(array &$sandbox): void {
  if (!FieldStorageConfig::loadByName('paragraph', 'field_oe_image_copyright')) {
    FieldStorageConfig::create([
      'field_name' => 'field_oe_image_copyright',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'settings' => [
        'max_length' => 255,
        'is_ascii' => FALSE,
        'case_sensitive' => FALSE,
      ],
      'cardinality' => 1,
      'translatable' => TRUE,
    ])->save();
  }

  if (!FieldConfig::loadByName('paragraph', 'oe_list_item', 'field_oe_image_copyright')) {
    FieldConfig::create([
      'field_name' => 'field_oe_image_copyright',
      'entity_type' => 'paragraph',
      'bundle' => 'oe_list_item',
      'label' => 'Copyright',
      'description' => 'Copyright text displayed with the image.',
      'required' => FALSE,
      'translatable' => TRUE,
    ])->save();
  }

  $form_display = EntityFormDisplay::load('paragraph.oe_list_item.default');
  if ($form_display !== NULL) {
    $form_display->setComponent('field_oe_image_copyright', [
      'type' => 'string_textfield',
      'weight' => 4,
      'region' => 'content',
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
      'third_party_settings' => [],
    ]);
    $field_oe_meta = $form_display->getComponent('field_oe_meta');
    if (!empty($field_oe_meta)) {
      $field_oe_meta['weight'] = 5;
      $form_display->setComponent('field_oe_meta', $field_oe_meta);
    }
    $form_display->save();
  }
}

/**
 * Adds the alignment option to Facts and Figures paragraphs.
 */
function oe_whitelabel_paragraphs_post_update_00003(): void {
  if (!FieldStorageConfig::loadByName('paragraph', 'oe_w_alignment')) {
    FieldStorageConfig::create([
      'field_name' => 'oe_w_alignment',
      'entity_type' => 'paragraph',
      'type' => 'list_string',
      'settings' => [
        'allowed_values' => [
          'left' => 'Left',
          'center' => 'Center',
        ],
      ],
      'cardinality' => 1,
      'translatable' => TRUE,
    ])->save();
  }

  $field_config = FieldConfig::loadByName('paragraph', 'oe_facts_figures', 'oe_w_alignment');
  if ($field_config === NULL) {
    FieldConfig::create([
      'field_name' => 'oe_w_alignment',
      'entity_type' => 'paragraph',
      'bundle' => 'oe_facts_figures',
      'label' => 'Alignment',
      'description' => 'Aligns icons, values, and labels. Descriptions remain left-aligned.',
      'required' => FALSE,
      'translatable' => FALSE,
      'default_value' => [
        ['value' => 'left'],
      ],
    ])->save();
  }
  elseif ($field_config->isRequired()) {
    $field_config->setRequired(FALSE)->save();
  }

  $form_display = EntityFormDisplay::load('paragraph.oe_facts_figures.default');
  if ($form_display !== NULL) {
    $form_display->setComponent('oe_w_alignment', [
      'type' => 'options_select',
      'weight' => 7,
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [],
    ])->save();
  }

  $view_display = EntityViewDisplay::load('paragraph.oe_facts_figures.default');
  if ($view_display !== NULL) {
    $view_display->removeComponent('oe_w_alignment')->save();
  }
}
