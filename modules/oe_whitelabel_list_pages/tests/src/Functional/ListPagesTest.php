<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_list_pages_test\FunctionalJavascript;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the whitelabel oe_list_pages.
 */
class ListPagesTest extends WhitelabelBrowserTestBase {

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

    $this->drupalLogin($this->rootUser);

    // Create some test nodes.
    for ($i = 0; $i < 12; $i++) {
      $date = new DrupalDateTime('20-10-2020');
      $values = [
        'title' => 'News number ' . $i,
        'type' => 'oe_sc_news',
        'body' => 'This is content number ' . $i,
        'status' => NodeInterface::PUBLISHED,
        'created' => $date->getTimestamp(),
      ];
      $node = Node::create($values);
      $node->save();
    }

    // Index content.
    $list_source_factory = $this->container->get('oe_list_pages.list_source.factory');
    $item_list = $list_source_factory->get('node', 'oe_sc_news');
    $item_list->getIndex()->indexItems();

    // Create list page for News.
    $this->drupalGet('/node/add/oe_list_page');
    $page->fillField('Title', 'News list page');

    // Check that select bundle is required.
    $page->pressButton('Save');
    $assert_session->elementTextEquals('css', 'div.alert-content', 'Source bundle field is required.');

    $page->selectFieldOption('Source bundle', 'News');
    $page->pressButton('Save');

    $assert_session->pageTextMatchesCount(10, '/News number/');
    $assert_session->elementNotExists('css', 'h4.offcanvas-title');
    $assert_session->elementContains('css', 'h4.mb-0 > span', 'News list page');
    $assert_session->elementContains('css', 'h4.mb-0', '(12)');
    $assert_session->elementExists('css', 'nav > ul.pagination');
    $assert_session->elementsCount('css', 'div.bcl-listing.bcl-listing--default-1-col > div.row > div.col > article > div.listing-item', '10');
    $assert_session->elementsCount('css', 'hr', 2);
    $assert_session->elementsCount('css', 'ul.pagination > li.page-item', 3);

    // Add facets filters.
    $list_page = $this->getNodeByTitle('News list page');
    $this->drupalGet('node/' . $list_page->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oe_sc_news_title]');
    $page->pressButton('Save');

    $assert_session->elementTextEquals('css', 'h4.offcanvas-title', 'Filter options');
    $assert_session->elementExists('css', 'div.bcl-offcanvas');
    $assert_session->elementExists('css', 'input[name="oe_sc_news_title"]');
    $assert_session->elementExists('css', 'button.btn-light > svg');

    $page->fillField('Title', 'number');
    $page->pressButton('Search');

    $assert_session->elementTextEquals('css', 'span.badge.bg-light', 'number');
  }

}
