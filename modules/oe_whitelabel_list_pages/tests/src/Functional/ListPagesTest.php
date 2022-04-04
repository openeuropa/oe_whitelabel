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

    // Create list for content type one.
    $this->drupalLogin($this->rootUser);

    // Create some test nodes to index and search in.
    for ($i = 0; $i < 11; $i++) {
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

    $list_source_factory = $this->container->get('oe_list_pages.list_source.factory');
    $item_list = $list_source_factory->get('node', 'oe_sc_news');
    $item_list->getIndex()->indexItems();

    $this->drupalGet('/node/add/oe_list_page');

    $page->fillField('Title', 'News list page');
    $page->selectFieldOption('Source bundle', 'News');
    $page->pressButton('Save');

    $list_page = $this->getNodeByTitle('News list page');
    $this->drupalGet('node/' . $list_page->id() . '/edit');
    $page->checkField('Override default exposed filters');
    $page->checkField('emr_plugins_oe_list_page[wrapper][exposed_filters][oe_sc_news_title]');
    $page->pressButton('Save');

    $assert_session->elementExists('css', 'div.bcl-offcanvas');
    $assert_session->elementExists('css', 'input[name="oe_sc_news_title"]');
    $assert_session->elementExists('css', 'button.btn-light > svg');
    $assert_session->elementExists('css', 'nav > ul.pagination');
    $assert_session->elementsCount('css', 'div.bcl-listing.bcl-listing--default-1-col > div.row > div.col > article > div.listing-item', '10');
    $assert_session->elementsCount('css', 'hr', '2');
    $assert_session->elementsCount('css', 'ul.pagination > li.page-item', '3');

  }

}
