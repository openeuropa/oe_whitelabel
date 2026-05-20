<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\oe_whitelabel\Traits\CckContainerTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Base class for testing content types.
 */
abstract class WhitelabelBrowserTestBase extends BrowserTestBase {

  use SparqlConnectionTrait;
  use CckContainerTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assert the cck container exists on all functional tests pages.
    $this->assertCckContainer();

    // Without sparql, some blocks will cause problems.
    $this->setUpSparql();
  }

  /**
   * Creates the media copyright field and attaches it to media bundles.
   *
   * @param array $bundles
   *   The media bundles to attach the field to.
   */
  protected function createMediaCopyrightField(array $bundles = ['image']): void {
    if (!FieldStorageConfig::loadByName('media', 'field_media_copyright')) {
      FieldStorageConfig::create([
        'field_name' => 'field_media_copyright',
        'entity_type' => 'media',
        'type' => 'string',
      ])->save();
    }

    foreach ($bundles as $bundle) {
      if (FieldConfig::loadByName('media', $bundle, 'field_media_copyright')) {
        continue;
      }

      FieldConfig::create([
        'field_name' => 'field_media_copyright',
        'entity_type' => 'media',
        'bundle' => $bundle,
        'label' => 'Copyright',
      ])->save();
    }
  }

}
