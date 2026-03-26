<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\oe_whitelabel_paragraphs\Kernel\PatternAssertions\ListingAssertion;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
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
    $copyright = $crawler->filter('article.listing-item .bcl-card__image-footer .bcl-copyright');
    $this->assertCount(1, $copyright);
    $this->assertEquals('(c) 2026 Listing test copyright', trim($copyright->text()));

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
   * Tests list item block rendering with formatted text.
   */
  public function testListingRichText(): void {
    FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ])->save();

    $field_config = FieldConfig::load('paragraph.oe_list_item.field_oe_text_long');
    $this->assertNotNull($field_config);
    $settings = $field_config->get('settings');
    $settings['allowed_formats'] = ['plain_text', 'filtered_html'];
    $field_config->set('settings', $settings)->save();

    $list_item = Paragraph::create([
      'type' => 'oe_list_item',
      'field_oe_title' => 'Item title 1',
      'field_oe_text_long' => [
        'value' => '<p id="listing-rich-text">I add a text with <strong>bolds</strong>, <em>italic</em> and <a href="https://www.google.es">loopy link</a></p>',
        'format' => 'filtered_html',
      ],
    ]);
    $list_item->save();

    $paragraph = Paragraph::create([
      'type' => 'oe_list_item_block',
      'oe_paragraphs_variant' => 'default',
      'field_oe_list_item_block_layout' => 'one_column',
      'field_oe_title' => 'Listing item block title',
      'field_oe_paragraphs' => [$list_item],
    ]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $this->assertRichTextRendering($crawler, 'article.listing-item .card-text');

    $paragraph->get('oe_paragraphs_variant')->setValue('highlight');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $this->assertRichTextRendering($crawler, 'article.listing-item--highlight .card-text');
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
        'field_oe_image_copyright' => $i === 1 ? '(c) 2026 Listing test copyright' : '',
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

  /**
   * Asserts formatted listing text renders through processed text filtering.
   */
  protected function assertRichTextRendering(Crawler $crawler, string $selectorPrefix): void {
    $this->assertCount(1, $crawler->filter($selectorPrefix . ' p#listing-rich-text'));
    $this->assertCount(1, $crawler->filter($selectorPrefix . ' strong'));
    $this->assertCount(1, $crawler->filter($selectorPrefix . ' em'));
    $this->assertCount(1, $crawler->filter($selectorPrefix . ' a[href="https://www.google.es"]'));
    $this->assertSame('bolds', trim($crawler->filter($selectorPrefix . ' strong')->html()));
    $this->assertSame('italic', trim($crawler->filter($selectorPrefix . ' em')->html()));
    $this->assertSame('loopy link', trim($crawler->filter($selectorPrefix . ' a[href="https://www.google.es"]')->html()));
  }

}
