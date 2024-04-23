<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the color scheme.
 */
class ColorSchemeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_color_scheme',
    'oe_paragraphs_banner',
    'oe_whitelabel_helper',
    'oe_paragraphs_carousel',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel_test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'test_ct',
      'name' => 'Test content type',
    ]);

    FieldStorageConfig::create([
      'field_name' => 'oe_w_colorscheme',
      'entity_type' => 'node',
      'type' => 'oe_color_scheme',
    ])->save();

    FieldConfig::create([
      'label' => 'ColorScheme field',
      'field_name' => 'oe_w_colorscheme',
      'entity_type' => 'node',
      'bundle' => 'test_ct',
    ])->save();

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'test_ct');
    $form_display = $form_display->setComponent('oe_w_colorscheme', [
      'region' => 'content',
      'type' => 'oe_color_scheme_widget',
    ]);
    $form_display->save();
  }

  /**
   * Tests that the color scheme is injected into node and paragraphs.
   */
  public function testColorScheme(): void {
    $node = Node::create([
      'type' => 'test_ct',
      'title' => 'Test',
      'oe_w_colorscheme' => [
        'name' => 'foo_bar',
      ],
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $assert_session = $this->assertSession();

    $assert_session->elementExists('css', '.foo_bar');
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
        'bundle' => $data['bundle'],
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
      'bundle' => 'oe_accordion',
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
      'bundle' => 'oe_banner',
      'values' => [
        'type' => 'oe_banner',
        'oe_paragraphs_variant' => 'default',
        'field_oe_title' => 'Banner',
        'field_oe_text' => 'Description',
      ],
      'wrapper_selector' => '.bcl-banner',
    ];
    yield [
      'bundle' => 'oe_quote',
      'values' => [
        'type' => 'oe_quote',
        'field_oe_text' => 'This is a test quote',
        'field_oe_plain_text_long' => 'Quote text',
      ],
      'wrapper_selector' => 'figure',
    ];
  }

  /**
   * Render a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph entity.
   * @param string|null $langcode
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

    return (string) $this->container->get('renderer')->renderRoot($render);
  }

}
