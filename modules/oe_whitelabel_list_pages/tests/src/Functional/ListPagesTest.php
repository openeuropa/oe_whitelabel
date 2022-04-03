<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_list_pages_test\FunctionalJavascript;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
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
    'oe_list_pages_content_type',
    'oe_whitelabel_list_pages_test',
  ];

  /**
   * Test fields an oe_whitelabel list page.
   */
  public function testListPage(): void {
    // Create list for content type one.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('<front>');
    $this->drupalGet('/node/add/oe_list_page');
    $page = $this->getSession()->getPage();
    $this->drupalGet('/node/add/oe_list_page');
    $this->clickLink('List Page');

    $page->selectFieldOption('Source bundle', 'News');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->checkField('Override default exposed filters');
    $page->checkField('Title');
    $page->pressButton('Save');

    // Create some test nodes to index and search in.
    for ($i = 0; $i < 6; $i++) {
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

    /** @var \Drupal\search_api\Entity\Index $index */
    $index = Index::load('node');
    // Index the nodes.
    $index->indexItems();

    // Check fields are visible in list nodes.
    $node = $this->drupalGetNodeByTitle('Node number 5');
    $this->drupalGet($node->toUrl());
    $this->assertSession()->fieldExists('Title');
    $assert = $this->assertSession();
    $assert->pageTextContains('This is content number 5');
  }

}
