<?php

/**
 * @file
 * Theme and preprocess functions for layouts.
 */

/**
 * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
 */
function oe_whitelabel_plugin_filter_layout__layout_builder_alter(array &$definitions, array $extra) {
  // Remove layouts provided by layout builder module.
  $extra_layouts = [
    'layout_onecol',
    'layout_twocol_section',
    'layout_threecol_section',
    'layout_fourcol_section',
  ];

  foreach ($extra_layouts as $extra_layout) {
    /** @var \Drupal\Core\Layout\LayoutDefinition[] $definitions */
    unset($definitions[$extra_layout]);
  }
}

/**
 * Theme preprocess function.
 *
 * Adds classes from the layout definition.
 *
 * @param array $variables
 *   Variables sent to the template.
 * @param string $hook
 *   Theme hook name.
 * @param array $info
 *   Theme hook info.
 */
function oe_whitelabel_preprocess_layout(array &$variables, string $hook, array $info) {
  /** @var \Drupal\Core\Layout\LayoutDefinition $layout */
  $layout = $variables['layout'];

  // Look for custom classes in the layout definition.
  $custom_classes = array_map(static function ($classes) {
    if (is_array($classes)) {
      return $classes;
    }
    return array_filter(explode(' ', $classes));
  }, $layout->get('oe_whitelabel_custom_classes') ?: []);

  $classes_by_region = array_map(static function (array $region) {
    $region_classes = $region['oe_whitelabel_custom_classes'] ?? [];
    if (!$region_classes) {
      return [];
    }
    if (is_array($region_classes)) {
      return $region_classes;
    }
    return array_filter(explode(' ', $region_classes));
  }, $layout->getRegions());

  /** @var \Drupal\Core\Template\Attribute $region_attributes */
  foreach ($variables['region_attributes'] ?? [] as $region_name => $region_attributes) {
    $region_attributes->addClass($custom_classes['region'] ?? []);
    $region_attributes->addClass($classes_by_region[$region_name] ?? []);
  }

  if (!empty($custom_classes['layout'])) {
    foreach ($custom_classes['layout'] ?? [] as $class) {
      $variables['attributes']['class'][] = $class;
    }
  }
}
