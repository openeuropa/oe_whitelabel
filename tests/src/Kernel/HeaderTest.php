<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * Tests the EU and the EC corporate Header rendering.
 */
class HeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'components',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
    'user',
    'system',
    'oe_whitelabel_helper',
    'multivalue_form_element',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    $this->container->set('theme.registry', NULL);
    $this->container->get('cache.render')->deleteAll();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testEcHeaderRendering(): void {}

}
