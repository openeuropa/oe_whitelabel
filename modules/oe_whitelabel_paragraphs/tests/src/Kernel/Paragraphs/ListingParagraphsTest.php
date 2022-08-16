<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\oe_whitelabel_paragraphs\Kernel\PatternAssertions\ListingAssertion;
use Drupal\Tests\TestFileCreationTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the rendering of paragraph Listing.
 */
class ListingParagraphsTest extends ParagraphsTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Test List Items Block paragraph rendering.
   */
  public function testListing(): void {
    // Create a sample media entity to be embedded.
    $image_file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $image_file->setPermanent();
    $image_file->save();

    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $node = $this->createNode([
      'type' => 'article',
    ]);
    $nid = (int) $node->id();

    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_list_item_block',
      'oe_paragraphs_variant' => 'default',
      'field_oe_list_item_block_layout' => 'one_column',
      'field_oe_title' => 'Listing item block title',
      'field_oe_paragraphs' => $this->createListItems($image_file, $node),
    ]);
    $paragraph->save();

    // Testing Default 1 col.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert = new ListingAssertion();

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertDefaultListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--default-1-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1'));
    $this->assertCount(6, $crawler->filter('div.card-body'));

    // Testing Default 2 col.
    $paragraph->get('field_oe_list_item_block_layout')->setValue('two_columns');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertDefaultListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--default-2-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1'));
    $this->assertCount(6, $crawler->filter('div.card-body'));

    // Testing Default 3 col.
    $paragraph->get('field_oe_list_item_block_layout')->setValue('three_columns');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertDefaultListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--default-3-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1'));
    $this->assertCount(6, $crawler->filter('div.card-body'));

    // Testing Highlight 1 col.
    $paragraph->get('oe_paragraphs_variant')->setValue('highlight');
    $paragraph->get('field_oe_list_item_block_layout')->setValue('one_column');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertHighlightListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--highlight-1-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1'));
    $this->assertCount(6, $crawler->filter('div.card-body'));

    // Testing Highlight 2 col.
    $paragraph->get('field_oe_list_item_block_layout')->setValue('two_columns');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertHighlightListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--highlight-2-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1.row-cols-md-2'));
    $this->assertCount(6, $crawler->filter('article.listing-item--highlight'));
    $this->assertCount(6, $crawler->filter('div.card-body'));

    // Testing Highlight 3 col.
    $paragraph->get('field_oe_list_item_block_layout')->setValue('three_columns');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $assert->assertListingRendering($crawler, $nid);
    $assert->assertHighlightListingRendering($crawler, $image_file);
    $this->assertCount(1, $crawler->filter('div.bcl-listing--highlight-3-col'));
    $this->assertCount(1, $crawler->filter('div.row.row-cols-1.row-cols-md-3'));
    $this->assertCount(6, $crawler->filter('article.listing-item--highlight'));
    $this->assertCount(6, $crawler->filter('div.card-body'));
  }

  /**
   * Assert default variant of Listing is rendering correctly.
   *
   * @param \Drupal\file\Entity\File $image_file
   *   Image file to be added to the list item.
   * @param \Drupal\node\Entity\Node $node
   *   A Node entity.
   */
  protected function createListItems(File $image_file, Node $node): array {
    $items = [];
    for ($i = 1; $i <= 6; $i++) {
      $paragraph = Paragraph::create([
        'type' => 'oe_list_item',
        'oe_paragraphs_variant' => 'default',
        'field_oe_title' => 'Item title ' . $i,
        'field_oe_text_long' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus ut ex tristique, dignissim sem ac, bibendum est. ' . $i,
        'field_oe_link' => [
          'uri' => 'entity:node/' . $node->id(),
          'title' => 'Example ' . $i,
        ],
        'field_oe_image' => [
          'alt' => 'Alt for image ' . $i,
          'target_id' => $image_file->id(),
        ],
        'field_oe_meta' => [
          0 => [
            'value' => 'Label 1 - ' . $i,
          ],
          1 => [
            'value' => 'Label 2 - ' . $i,
          ],
        ],
      ]);
      $paragraph->save();
      $items[$i] = $paragraph;
    }

    return $items;
  }

}
