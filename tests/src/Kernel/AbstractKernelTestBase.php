<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\oe_bootstrap_theme\Kernel\AbstractKernelTestBase as OebtAbstractKernelTestBase;
use Drupal\Tests\oe_bootstrap_theme\Kernel\Traits\RenderTrait;

/**
 * Base class for theme's kernel tests.
 */
abstract class AbstractKernelTestBase extends OebtAbstractKernelTestBase {

  use RenderTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_helper',
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
