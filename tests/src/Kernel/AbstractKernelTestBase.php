<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\oe_bootstrap_theme\Kernel\AbstractKernelTestBase as BootstrapKernelTestBase;

/**
 * Base class for theme's kernel tests.
 */
abstract class AbstractKernelTestBase extends BootstrapKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'daterange_compact',
    'oe_whitelabel_helper',
    'oe_corporate_blocks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'oe_whitelabel_helper',
    ]);

    $this->container->get('theme_installer')->install(['oe_whitelabel']);
    $this->config('system.theme')->set('default', 'oe_whitelabel')->save();
    $this->container->set('theme.registry', NULL);
  }

}
