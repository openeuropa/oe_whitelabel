<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_list_pages\Functional;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the list pages rendering.
 */
class ListPagesTest extends WhitelabelBrowserTestBase {

  use ExampleContentTrait;
  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_list_pages_test',
  ];

  /**
   * Tests a list page node rendering.
   */
  public function testListPageRendering(): void {
    $assert_session = $this->assertSession();

    // Create some test nodes.
    for ($i = 0; $i < 12; $i++) {
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is content number ' . $i,
        'status' => NodeInterface::PUBLISHED,
        'created' => '2020-10-20',
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $this->indexItems('oe_whitelabel_list_page_index_test');
    $list_page = $this->createListPage();

    $this->drupalGet('node/' . $list_page->id());

    // Assert the left column.
    $left_column = $assert_session->elementExists('css', 'div.row > .col-lg-3');

    // Assert offcanvas.
    $offcanvas = $left_column->find('css', 'div.bcl-offcanvas');
    $title = $offcanvas->find('css', 'h4.offcanvas-title');
    $this->assertSame('Filter options', $title->getText());
    $offcanvas->hasField('Title');
    $offcanvas->hasButton('Search');
    $offcanvas->hasButton('Clear filters');
    $offcanvas->hasButton('Filters');

    // Assert right column.
    $right_column = $assert_session->elementExists('css', 'div.row > .col-lg-9');
    $assert_session->elementsCount('css', 'hr', 2, $right_column);

    $this->assertFacetsSummaryTitle(12, $right_column);
    $this->assertActiveFilterBadges([], $right_column);
    $this->assertListing(10, $right_column);
    $this->assertPager(4, $right_column);

    // Use a filter to get a badge.
    $page->fillField('Title', 'News number 8');
    $page->pressButton('Search');

    $this->assertFacetsSummaryTitle(1, $right_column);
    $this->assertActiveFilterBadges(['News number 8'], $right_column);
    $this->assertListing(1, $right_column);
    $this->assertPager(0, $right_column);
  }

  /**
   * Create a list page node with filters configured.
   *
   * @return \Drupal\node\NodeInterface
   *   The list page node created.
   */
  protected function createListPage(): NodeInterface {
    $list_page = Node::create([
      'type' => 'oe_list_page',
      'title' => 'News list page',
    ]);

    /** @var \Drupal\emr\Entity\EntityMetaInterface $list_page_entity_meta */
    $list_page_entity_meta = $list_page->get('emr_entity_metas')->getEntityMeta('oe_list_page');
    /** @var \Drupal\oe_list_pages\ListPageWrapper $list_page_entity_meta_wrapper */
    $list_page_entity_meta_wrapper = $list_page_entity_meta->getWrapper();
    $list_page_entity_meta_wrapper->setSource('node', 'oe_sc_news');
    $list_page_entity_meta_wrapper->setConfiguration([
      'override_exposed_filters' => 1,
      'exposed_filters' => [
        'oe_sc_news_title' => 'oe_sc_news_title',
      ],
      'preset_filters' => [],
      'limit' => 10,
      'sort' => [],
    ]);
    $list_page->get('emr_entity_metas')->attach($list_page_entity_meta);
    $list_page->save();

    return $list_page;
  }

    // Assert the left column.
    $assert_session->elementExists('css', 'div.row > div.col-12.col-lg-3');
    // Assert offcanvas.
    $assert_session->elementExists('css', 'div.bcl-offcanvas');
    $assert_session->elementTextEquals('css', 'h4.offcanvas-title', 'Filter options');
    $assert_session->elementExists('css', 'button.btn-light > svg');
    $assert_session->elementTextEquals('css', 'button[data-bs-toggle="offcanvas"]', 'Filters');
    // Assert Filters.
    $assert_session->elementExists('css', 'input[name="oe_sc_news_title"]');
    $assert_session->elementExists('css', 'input[data-drupal-selector="edit-submit"]');
    $assert_session->elementExists('css', 'input[data-drupal-selector="edit-reset"]');

    // Assert right column.
    $assert_session->elementExists('css', 'div.row > div.col-12.col-lg-9.col-xxl-8');
    $assert_session->elementContains('css', 'h4.mb-0 > span', 'News list page');
    $assert_session->elementContains('css', 'h4.mb-0', '(12)');
    // Assert listing.
    $assert_session->elementsCount('css', 'hr', 2);
    $assert_session->elementsCount('css', 'div.listing-item', '10');
    // Assert pagination.
    $assert_session->elementExists('css', 'nav > ul.pagination');
    $assert_session->elementsCount('css', 'ul.pagination > li.page-item', 4);

    // Assert search.
    $page->fillField('Title', 'News number 8');
    $page->pressButton('Search');
    $assert_session->elementContains('css', 'h4.mb-0', '(1)');
    $assert_session->elementTextEquals('css', 'span.badge.bg-light', 'News number 8');
  }

}
