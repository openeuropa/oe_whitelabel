<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CarouselPatternAssert;

/**
 * Tests the Carousel paragraph.
 */
class CarouselTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'composite_reference',
    'content_translation',
    'node',
    'oe_multilingual',
    'oe_paragraphs_carousel',
    'oe_whitelabel_paragraphs',
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
      'content_translation',
      'media',
      'oe_media',
      'oe_paragraphs_carousel',
      'oe_paragraphs_media',
    ]);
    // Call the install hook of the Media module.
    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
  }

  /**
   * Tests the file paragraph rendering.
   */
  public function testRendering(): void {
    // Set image media translatable.
    $this->container->get('content_translation.manager')
      ->setEnabled('media', 'image', TRUE);
    // Make the image field translatable.
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('media.image.oe_media_image');
    $field_config->set('translatable', TRUE)->save();
    $this->container->get('router.builder')->rebuild();

    $fixtures_path = \Drupal::service('extension.list.module')->getPath('oe_whitelabel_paragraphs') . '/tests/fixtures/';
    // Create English files.
    $en_file_1 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1_en.jpeg');
    $en_file_1->setPermanent();
    $en_file_1->save();
    $en_file_2 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_2_en.jpeg');
    $en_file_2->setPermanent();
    $en_file_2->save();

    // Create Bulgarian files.
    $bg_file_1 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1_bg.jpeg');
    $bg_file_1->setPermanent();
    $bg_file_1->save();
    $bg_file_2 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_2_bg.jpeg');
    $bg_file_2->setPermanent();
    $bg_file_2->save();

    // Create a couple of media items with Bulgarian translation.
    $media_storage = $this->container->get('entity_type.manager')
      ->getStorage('media');
    $first_media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'First image en',
      'oe_media_image' => [
        'target_id' => $en_file_1->id(),
      ],
    ]);
    $first_media->save();
    $first_media_bg = $first_media->addTranslation('bg', [
      'name' => 'First image bg',
      'oe_media_image' => [
        'target_id' => $bg_file_1->id(),
      ],
    ]);
    $first_media_bg->save();
    $second_media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'Second image en',
      'oe_media_image' => [
        'target_id' => $en_file_2->id(),
      ],
    ]);
    $second_media->save();
    $second_media_bg = $second_media->addTranslation('bg', [
      'name' => 'Second image bg',
      'oe_media_image' => [
        'target_id' => $bg_file_2->id(),
      ],
    ]);
    $second_media_bg->save();

    // Create a few Carousel items paragraphs with Bulgarian translation.
    $items = [];
    for ($i = 1; $i <= 4; $i++) {
      $paragraph = Paragraph::create([
        'type' => 'oe_carousel_item',
        'field_oe_title' => 'Item ' . $i,
        'field_oe_text' => $i % 2 === 0 ? 'Item description ' . $i : '',
        'field_oe_link' => $i % 2 === 0 ? [
          // Make sure that URI properly handled.
          'uri' => $i === 4 ? 'route:<front>' : 'http://www.example.com/',
          'title' => 'CTA ' . $i,
        ] : [],
        'field_oe_media' => $i % 2 !== 0 ? $first_media : $second_media,
      ]);
      $paragraph->save();
      $paragraph->addTranslation('bg', [
        'field_oe_title' => 'BG Item ' . $i,
        'field_oe_text' => $i % 2 === 0 ? 'BG Item description ' . $i : '',
        'field_oe_link' => $i % 2 === 0 ? [
          'uri' => 'http://www.example.com/',
          'title' => 'BG CTA ' . $i,
        ] : [],
      ])->save();
      $items[$i] = $paragraph;
    }
    // Create a Carousel paragraph with Bulgarian translation.
    $paragraph = Paragraph::create([
      'type' => 'oe_carousel',
      'field_oe_carousel_items' => $items,
    ]);
    $paragraph->save();
    $paragraph->addTranslation('bg', $paragraph->toArray())->save();

    // Assert paragraph rendering for English version.
    $html = $this->renderParagraph($paragraph);
    $assert = new CarouselPatternAssert();
    $expected_values = [
      'items' => [
        [
          'caption_title' => 'Item 1',
          'image' => [
            'src' => file_create_url($en_file_1->getFileUri()),
            'alt' => 'First image en',
          ],
        ],
        [
          'caption_title' => 'Item 2',
          'caption' => 'Item description 2',
          'link' => [
            'label' => 'CTA 2',
            'path' => 'http://www.example.com/',
          ],
          'image' => [
            'src' => file_create_url($en_file_2->getFileUri()),
            'alt' => 'Second image en',
          ],
        ],
        [
          'caption_title' => 'Item 3',
          'image' => [
            'src' => file_create_url($en_file_1->getFileUri()),
            'alt' => 'First image en',
          ],
        ],
        [
          'caption_title' => 'Item 4',
          'caption' => 'Item description 4',
          'link' => [
            'label' => 'CTA 4',
            'path' => '/',
          ],
          'image' => [
            'src' => file_create_url($en_file_2->getFileUri()),
            'alt' => 'Second image en',
          ],
        ],
      ],
    ];
    $assert->assertPattern($expected_values, $html);

    // Assert paragraph rendering for Bulgarian version.
    $html = $this->renderParagraph($paragraph, 'bg');
    $expected_values = [
      'items' => [
        [
          'caption_title' => 'BG Item 1',
          'image' => [
            'src' => file_create_url($bg_file_1->getFileUri()),
            'alt' => 'First image bg',
          ],
        ],
        [
          'caption_title' => 'BG Item 2',
          'caption' => 'BG Item description 2',
          'link' => [
            'label' => 'BG CTA 2',
            'path' => 'http://www.example.com/',
          ],
          'image' => [
            'src' => file_create_url($bg_file_2->getFileUri()),
            'alt' => 'Second image bg',
          ],
        ],
        [
          'caption_title' => 'BG Item 3',
          'image' => [
            'src' => file_create_url($bg_file_1->getFileUri()),
            'alt' => 'First image bg',
          ],
        ],
        [
          'caption_title' => 'BG Item 4',
          'caption' => 'BG Item description 4',
          'link' => [
            'label' => 'BG CTA 4',
            'path' => 'http://www.example.com/',
          ],
          'image' => [
            'src' => file_create_url($bg_file_2->getFileUri()),
            'alt' => 'Second image bg',
          ],
        ],
      ],
    ];

    $assert->assertPattern($expected_values, $html);
  }

}
