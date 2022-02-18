<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing content types.
 */
abstract class WhitelabelBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable and set OpenEuropa Theme as default.
    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    // Rebuild the ui_pattern definitions to collect the ones provided by
    // oe_whitelabel itself.
    \Drupal::service('plugin.manager.ui_patterns')->clearCachedDefinitions();
  }

}
