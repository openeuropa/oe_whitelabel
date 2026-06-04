<?php

/**
 * @file
 * This file contains version-specific PhpStan ignores.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$config = [];

if (version_compare(\Drupal::VERSION, '11.2', '>=')) {
  // @todo Fix when we drop support for Drupal 10.
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Call to deprecated function entity_test_create_bundle#',
    'identifier' => 'function.deprecated',
    'count' => 2,
    // In a .neon.php file, paths must be absolute.
    'path' => __DIR__ . '/modules/oe_whitelabel_link_lists/tests/src/Kernel/TeaserDisplayPluginListTest.php',
  ];
}
else {
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Attribute class Drupal\\\\Core\\\\Hook\\\\Attribute\\\\Hook does not exist\\.$#',
    'identifier' => 'attribute.notFound',
    'count' => 1,
    // In a .neon.php file, paths must be absolute.
    'path' => __DIR__ . '/modules/oe_whitelabel_modal_page/src/Hook/ModalEntityTypeFormClass.php',
  ];
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#^Attribute class Drupal\\\\Core\\\\Hook\\\\Attribute\\\\Hook does not exist\\.$#',
    'identifier' => 'attribute.notFound',
    'count' => 1,
    // In a .neon.php file, paths must be absolute.
    'path' => __DIR__ . '/src/Hook/ThemeRegistryAlterPreprocessRequiredModule.php',
  ];
}

return $config;
