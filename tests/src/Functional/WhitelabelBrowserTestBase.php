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
  protected $defaultTheme = 'oe_whitelabel';

}
