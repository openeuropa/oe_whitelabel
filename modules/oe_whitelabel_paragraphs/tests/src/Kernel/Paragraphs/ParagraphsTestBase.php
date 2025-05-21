<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Tests\oe_whitelabel_paragraphs\Kernel\AbstractKernelTestBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Base class for paragraphs tests.
 */
abstract class ParagraphsTestBase extends AbstractKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'description_list_field',
    'entity_browser',
    'entity_reference_revisions',
    'field',
    'file',
    'file_link',
    'filter',
    'language',
    'link',
    'locale',
    'media',
    'media_avportal',
    'media_avportal_mock',
    'node',
    'oe_media',
    'oe_media_avportal',
    'oe_media_iframe',
    'oe_paragraphs',
    'oe_paragraphs_banner',
    'oe_paragraphs_description_list',
    'oe_paragraphs_iframe_media',
    'oe_paragraphs_media',
    'oe_paragraphs_media_field_storage',
    'oe_whitelabel_paragraphs',
    'options',
    'paragraphs',
    'text',
    'typed_link',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);
    $this->installConfig([
      'oe_paragraphs',
      'oe_paragraphs_description_list',
      'oe_whitelabel_paragraphs',
      'filter',
      'locale',
      'language',
      'node',
    ]);
  }

  /**
   * Render a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph entity.
   * @param string|null $langcode
   *   Rendering language code.
   *
   * @return string
   *   Rendered output.
   *
   * @throws \Exception
   */
  protected function renderParagraph(ParagraphInterface $paragraph, ?string $langcode = NULL): string {
    $render = \Drupal::entityTypeManager()
      ->getViewBuilder('paragraph')
      ->view($paragraph, 'default', $langcode);

    return $this->renderRoot($render);
  }

  /**
   * Test the 'allowed_formats' setting for text fields.
   */
  public function testAllowedFormats(): void {
    $field_ids = [
      'paragraph.oe_list_item.field_oe_text_long',
    ];

    foreach ($field_ids as $field_id) {
      $field_config = FieldConfig::load($field_id);
      $this->assertNotNull($field_config);
      $settings = $field_config->get('settings');
      $this->assertArrayHasKey('allowed_formats', $settings);
      $this->assertEquals(['plain_text'], $settings['allowed_formats']);
    }
  }

  /**
   * Test the entity form and view displays.
   */
  public function testEntityDisplays(): void {
    $display_ids = [
      'paragraph.oe_list_item.default',
    ];

    foreach ($display_ids as $display_id) {
      $form_display = EntityFormDisplay::load($display_id);
      $this->assertNotNull($form_display);
      $view_display = EntityViewDisplay::load($display_id);
      $this->assertNotNull($view_display);
    }
  }

}
