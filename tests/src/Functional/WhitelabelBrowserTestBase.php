<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Base class for testing content types.
 */
abstract class WhitelabelBrowserTestBase extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Without sparql, some blocks will cause problems.
    $this->setUpSparql();
  }

}
