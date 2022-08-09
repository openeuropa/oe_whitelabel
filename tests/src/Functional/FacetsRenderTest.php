<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\facets\Entity\Facet;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the Facets rendering.
 */
class FacetsRenderTest extends WhitelabelBrowserTestBase {

  use BlockTestTrait;
  use ExampleContentTrait;
  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_search_api_dependency',
    'facets_summary',
    'oe_whitelabel_helper',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpSparql();
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertSame(5, $this->indexItems('database_search_index'));
  }

  /**
   * Tests facets summary block rendering.
   */
  public function testFacetsSummaryBlock(): void {
    $this->createFacet('Emu', 'emu', 'type', 'page_1', 'views_page__search_api_test_view', FALSE);
    $facet = Facet::load('emu');
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->save();

    FacetsSummary::create([
      'name' => 'Owl',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facets' => [
        'emu' => [
          'checked' => TRUE,
          'show_count' => FALSE,
        ],
      ],
      'processor_configs' => [
        'show_count' => [
          'processor_id' => 'show_count',
        ],
      ],
    ])->save();

    $block = $this->placeBlock('facets_summary_block:owl', ['region' => 'content']);
    $this->drupalGet('search-api-test-fulltext');

    $assert = $this->assertSession();
    $assert->elementTextContains('css', 'main h4', $block->label());
    $assert->elementTextContains('css', 'span.source-summary-count', '(5)');
  }

  /**
   * Tests facets block rendering.
   */
  public function testFacetBlock(): void {
    $this->createFacet('Emu', 'emu', 'type', 'page_1', 'views_page__search_api_test_view', TRUE);
    $facet = Facet::load('emu');
    $facet->setWidget('checkbox');
    $facet->save();

    $this->createFacet('Pingu', 'pingu', 'type', 'page_1', 'views_page__search_api_test_view', TRUE);
    $facet = Facet::load('pingu');
    $facet->setWidget('dropdown');
    $facet->save();

    $this->createFacet('Lulu', 'lulu');

    $this->drupalGet('search-api-test-fulltext');
    $assert = $this->assertSession();
    $block = $assert->elementExists('css', '#block-emu');

    // Assert the block title rendering.
    $title_wrapper = $block->find('css', 'legend.col-form-label');
    $this->assertNotNull($title_wrapper);
    $title = $title_wrapper->find('css', 'span.fieldset-legend');
    $this->assertNotNull($title);

    // Assert the checkbox list rendering.
    $list = $block->find('css', 'ul.oel-facets-checkbox-list');
    $this->assertFalse($list->hasClass('form-select'));
    $items = $list->findAll('css', 'li.mb-3');
    $this->assertCount(2, $items);

    foreach ($items as $item) {
      $label = $item->find('css', 'span.ms-2.form-check-label');
      $this->assertNotNull($label);
    }

    // Assert the dropdown list rendering.
    $block = $assert->elementExists('css', '#block-pingu');
    $list = $block->find('css', 'ul.form-select');
    $this->assertFalse($list->hasClass('oel-facets-checkbox-list'));
    $items = $list->findAll('css', 'li.mb-3');
    $this->assertCount(2, $items);

    foreach ($items as $item) {
      $label = $item->find('css', 'span');
      $this->assertFalse($label->hasClass('form-check-label'));
      $this->assertFalse($label->hasClass('ms-2'));
    }

    // Assert the links list rendering.
    $block = $assert->elementExists('css', '#block-lulu');
    $list = $block->find('css', 'ul');
    $this->assertFalse($list->hasClass('form-select'));
    $this->assertFalse($list->hasClass('oel-facets-checkbox-list'));
    $items = $list->findAll('css', 'li.mb-3');
    $this->assertCount(2, $items);

    foreach ($items as $item) {
      $label = $item->find('css', 'span');
      $this->assertFalse($label->hasClass('form-check-label'));
      $this->assertFalse($label->hasClass('ms-2'));
    }
  }

}
