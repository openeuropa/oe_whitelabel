<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_list_pages_test\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the whitelabel oe_list_pages.
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
   * Test fields an oe_whitelabel list page.
   */
  public function testListPage(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create some test nodes.
    for ($i = 0; $i < 12; $i++) {
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is content number ' . $i,
        'status' => NodeInterface::PUBLISHED,
        'created' => sprintf('2022-04-%02d', $i + 1),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $this->indexItems('oe_whitelabel_list_page_index_test');

    $list_page = Node::create([
      'type'        => 'oe_list_page',
      'title'       => 'News list page',
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

    $this->drupalGet('node/' . $list_page->id());

    // Assert the left column.
    $left_column = $assert_session->elementExists('css', '#oe-list-pages-left-column');

    // Assert offcanvas.
    $offcanvas = $left_column->find('css', '#bcl-offcanvas');
    $title = $offcanvas->find('css', 'h4');
    $this->assertSame('Filter options', $title->getText());
    $offcanvas->hasButton('Filters');

    // Assert Filters and buttons.
    $offcanvas->hasField('Title');
    $offcanvas->hasButton('Search');
    $offcanvas->hasButton('Clear filters');

    // Assert right column.
    $right_column = $assert_session->elementExists('css', '#oe-list-pages-right-column');
    $facets_summary = $right_column->find('css', 'h4');
    $this->assertSame('News list page (12)', $facets_summary->getText());

    // Assert listing.
    $hr = $right_column->findAll('css', 'hr');
    $this->assertCount(2, $hr);
    $items = $right_column->findAll('css', '.listing-item');
    $this->assertCount(10, $items);

    // Assert pagination.
    $navigation = $right_column->find('css', 'nav');
    $pages = $navigation->findAll('css', 'li');
    $this->assertCount(3, $pages);

    // Assert search.
    $offcanvas->fillField('Title', 'News number 8');
    $offcanvas->pressButton('Search');
    $facets_summary = $right_column->find('css', 'h4');
    $this->assertSame('News list page (1)', $facets_summary->getText());
    $items = $right_column->findAll('css', '.listing-item');
    $this->assertCount(1, $items);
    $results = $right_column->find('css', '.bcl-listing');
    $this->assertSame('News number 8', $results->getText());
  }

}
