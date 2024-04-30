<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DomCrawler\Crawler;

class ColorSchemeTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_color_scheme',
    'oe_paragraphs_carousel',
    'oe_media_oembed_mock',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_handler')->loadInclude('oe_paragraphs_media_field_storage', 'install');
    oe_paragraphs_media_field_storage_install(FALSE);
    $this->installEntitySchema('media');
    $this->installConfig([
      'media',
      'oe_media',
      'oe_paragraphs_media',
      'media_avportal',
      'oe_media_avportal',
      'oe_paragraphs_banner',
      'oe_paragraphs_iframe_media',
      'options',
      'oe_media_iframe',
    ]);
    // Call the install hook of the Media module.
    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
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

      $element = $crawler->filter($data['wrapper_selector'] . '.foo_bar');
      $this->assertCount(1, $element);
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
    ];
    yield [
      'values' => [
        'type' => 'oe_banner',
        'oe_paragraphs_variant' => 'default',
        'field_oe_title' => 'Banner',
        'field_oe_text' => 'Description',
      ],
      'wrapper_selector' => '.bcl-banner',
    ];
    yield [
      'values' => [
        'type' => 'oe_quote',
        'field_oe_text' => 'This is a test quote',
        'field_oe_plain_text_long' => 'Quote text',
      ],
      'wrapper_selector' => 'figure',
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
    ];
  }

  /**
   * Render a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph entity.
   * @param string $langcode
   *   Rendering language code, defaults to 'en'.
   *
   * @return string
   *   Rendered output.
   *
   * @throws \Exception
   */
  protected function renderParagraph(ParagraphInterface $paragraph, string $langcode = NULL): string {
    $render = \Drupal::entityTypeManager()
      ->getViewBuilder('paragraph')
      ->view($paragraph, 'default', $langcode);

    return $this->renderRoot($render);
  }

}
