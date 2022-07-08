<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_list_pages\Functional;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests the list pages rendering.
 */
class ListPagesTest extends WhitelabelBrowserTestBase {

  use ExampleContentTrait;

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
    $page = $this->getSession()->getPage();
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

    $this->indexItems('oe_whitelabel_list_page_index_test');
    $list_page = $this->createListPage();

    $this->drupalGet('node/' . $list_page->id());

    // Assert the left column.
    $left_column = $assert_session->elementExists('css', 'div.row > .col-lg-3');

    // Assert offcanvas.
    $offcanvas = $left_column->find('css', 'div.bcl-offcanvas');
    $title = $offcanvas->find('css', 'h2.offcanvas-title');
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

  /**
   * Asserts the title in the facets summary.
   *
   * @param int $expected_count
   *   Expected number of results to be reported in the title.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   Container with the expected elements.
   */
  protected function assertFacetsSummaryTitle(int $expected_count, ElementInterface $container): void {
    $title = $container->find('css', 'div.col-md-6.col-lg-8 h4');
    $text = $title->find('css', 'span.text-capitalize');

    $this->assertSame(
      sprintf('%s (%s)', $text->getText(), $expected_count),
      $title->getText()
    );
  }

  /**
   * Asserts badges for active filters.
   *
   * @param string[] $expected
   *   Expected badge labels.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   Container with the expected elements.
   */
  protected function assertActiveFilterBadges(array $expected, ElementInterface $container): void {
    $badges = $container->findAll('css', '.badge');
    $this->assertElementsTexts($expected, $badges);
  }

  /**
   * Asserts link text on pager links.
   *
   * @param int $expected_count
   *   Expected number of links in the pager.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   Container with the expected elements.
   */
  protected function assertPager(int $expected_count, ElementInterface $container): void {
    $links = $container->findAll('css', 'ul.pagination > li.page-item');
    $this->assertCount($expected_count, $links);
  }

  /**
   * Asserts listing items.
   *
   * @param int $expected_count
   *   Expected number of results in the listing.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   Container with the expected elements.
   */
  protected function assertListing(int $expected_count, ElementInterface $container): void {
    $listing = $container->find('css', 'div.bcl-listing');
    $this->assertSession()->elementsCount('css', 'article.listing-item', $expected_count, $listing);
  }

  /**
   * Asserts text contents for multiple elements at once.
   *
   * @param string[] $expected
   *   Expected element texts.
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   Elements to compare.
   */
  protected function assertElementsTexts(array $expected, array $elements): void {
    $actual = [];
    foreach ($elements as $element) {
      $actual[] = $element->getText();
    }
    $this->assertSame($expected, $actual);
  }

}
