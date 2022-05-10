<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\facets\Entity\Facet;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the Facets Summary rendering.
 */
class FacetsSummaryTest extends WhitelabelBrowserTestBase {

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
    $facet->setWidget('links');
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

}
