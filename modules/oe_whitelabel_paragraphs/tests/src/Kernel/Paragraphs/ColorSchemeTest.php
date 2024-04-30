<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;

class ColorSchemeTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_color_scheme',
    'oe_content_timeline_field',
    'oe_paragraphs_carousel',
    'oe_paragraphs_document',
    'oe_paragraphs_timeline',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_handler')->loadInclude('oe_paragraphs_media_field_storage', 'install');
    oe_paragraphs_media_field_storage_install(FALSE);
    $this->installConfig([
      'media',
      'oe_media',
      'oe_paragraphs_banner',
      'oe_paragraphs_document',
      'oe_paragraphs_media',
      'oe_paragraphs_timeline',
    ]);
  }

  /**
   * Tests that the color scheme is injected into the paragraphs.
   */
  public function testColorSchemeInParagraphs(): void {
    FieldStorageConfig::create([
      'field_name' => 'oe_w_colorscheme',
      'entity_type' => 'paragraph',
      'type' => 'oe_color_scheme',
    ])->save();

    foreach ($this->paragraphSettingsProvider() as $data) {
      FieldConfig::create([
        'label' => 'ColorScheme field',
        'field_name' => 'oe_w_colorscheme',
        'entity_type' => 'paragraph',
        'bundle' => $data['values']['type'],
      ])->save();

      $paragraph = Paragraph::create($data['values'] + [
          'oe_w_colorscheme' => [
            'name' => 'foo_bar',
          ],
        ]);
      $paragraph->save();

      $html = $this->renderParagraph($paragraph);
      $crawler = new Crawler($html);

      $element = $crawler->filter($data['wrapper_selector'] . '.foo_bar.text-color-default');
      $this->assertCount(1, $element, sprintf('Element "%s" has color scheme applied.', $data['wrapper_selector']));
      $has_background = (bool) $crawler->filter('.bg-default')->count();
      $this->assertTrue($data['has_background'] === $has_background, sprintf('Element "%s" does not have ".bg-default" class.', $data['wrapper_selector']));
    }
  }

  /**
   * Data provider for testColorSchemeInParagraphs.
   *
   * @return \Generator
   *   The test data.
   */
  protected function paragraphSettingsProvider(): \Generator {
    yield [
      'values' => [
        'type' => 'oe_accordion',
        'field_oe_paragraphs' => Paragraph::create([
          'type' => 'oe_accordion_item',
          'field_oe_icon' => 'box-arrow-up',
          'field_oe_text' => 'Accordion item',
          'field_oe_text_long' => 'Accordion text',
        ]),
      ],
      'wrapper_selector' => '.accordion',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_banner',
        'oe_paragraphs_variant' => 'default',
        'field_oe_title' => 'Banner',
        'field_oe_text' => 'Description',
      ],
      'wrapper_selector' => '.bcl-banner',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_quote',
        'field_oe_text' => 'This is a test quote',
        'field_oe_plain_text_long' => 'Quote text',
      ],
      'wrapper_selector' => 'figure',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_description_list',
        'field_oe_title' => 'Description list paragraph',
        'oe_w_orientation' => 'horizontal',
        'field_oe_description_list_items' => [
          [
            'term' => 'Term',
            'description' => 'Description',
          ],
        ],
      ],
      'wrapper_selector' => '.bcl-description-list',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_document',
      ],
      'wrapper_selector' => '.paragraph--type--oe-document',
      'has_background' => FALSE,
    ];
    yield [
      'values' => [
        'type' => 'oe_facts_figures',
        'field_oe_title' => 'Facts and Figures test',
        'field_oe_link' => [
          'uri' => 'https://www.readmore.com',
          'title' => 'Read more',
        ],
      ],
      'wrapper_selector' => '.bcl-fact-figures',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_list_item_block',
        'oe_paragraphs_variant' => 'default',
        'field_oe_list_item_block_layout' => 'one_column',
        'field_oe_title' => 'Listing item block title',
      ],
      'wrapper_selector' => '.bcl-listing',
      'has_background' => FALSE,
    ];
    yield [
      'values' => [
        'type' => 'oe_text_feature_media',
        'field_oe_title' => 'Title',
        'field_oe_plain_text_long' => 'Text',
      ],
      'wrapper_selector' => '.bcl-featured-media',
      'has_background' => TRUE,
    ];
    yield [
      'values' => [
        'type' => 'oe_timeline',
        'field_oe_timeline_expand' => '3',
        'field_oe_timeline' => [
          [
            'label' => 'Label 1',
            'title' => 'Title 1',
            'body' => 'Description 1',
          ],
        ],
      ],
      'wrapper_selector' => '.bcl-timeline',
      'has_background' => TRUE,
    ];
  }

}
