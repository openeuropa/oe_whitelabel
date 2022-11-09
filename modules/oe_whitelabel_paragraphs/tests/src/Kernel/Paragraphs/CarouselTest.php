<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CarouselPatternAssert;
use Symfony\Component\DomCrawler\Crawler;

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
    'language',
    'media_avportal',
    'media_avportal_mock',
    'node',
    'oe_media_avportal',
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
      'language',
      'media_avportal',
      'oe_media',
      'oe_media_avportal',
      'oe_paragraphs_carousel',
      'oe_paragraphs_media',
    ]);
    // Call the install hook of the Media module.
    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();

    ConfigurableLanguage::createFromLangcode('bg')->save();
  }

  /**
   * Tests the file paragraph rendering.
   */
  public function testRendering(): void {
    // Set AV Photo as translatable.
    $this->container->get('content_translation.manager')
      ->setEnabled('media', 'av_portal_photo', TRUE);
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
    $en_image = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1_en.jpeg');
    $en_image->setPermanent();
    $en_image->save();

    // Create Bulgarian files.
    $bg_image = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1_bg.jpeg');
    $bg_image->setPermanent();
    $bg_image->save();

    // Create a couple of media items with Bulgarian translation.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $image_media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'First image en',
      'oe_media_image' => [
        'target_id' => $en_image->id(),
      ],
    ]);
    $image_media->save();
    $bg_image_translation = $image_media->addTranslation('bg', [
      'name' => 'First image bg',
      'oe_media_image' => [
        'target_id' => $bg_image->id(),
      ],
    ]);
    $bg_image_translation->save();

    $av_photo_media = $media_storage->create([
      'bundle' => 'av_portal_photo',
      'oe_media_avportal_photo' => 'P-038924/00-15',
    ]);
    $av_photo_media->save();
    $bg_av_photo_translation = $av_photo_media->addTranslation('bg', [
      'name' => 'AV Portal photo bg',
    ] + $av_photo_media->toArray());
    $bg_av_photo_translation->save();

    // Create a few Carousel items paragraphs with Bulgarian translation.
    $items = [];
    for ($i = 1; $i <= 4; $i++) {
      $item = Paragraph::create([
        'type' => 'oe_carousel_item',
        'field_oe_title' => 'Item ' . $i,
        'field_oe_text' => $i % 2 === 0 ? 'Item description ' . $i : '',
        'field_oe_link' => $i % 2 === 0 ? [
          // Make sure that URI properly handled.
          'uri' => $i === 4 ? 'route:<front>' : 'http://www.example.com/',
          'title' => 'CTA ' . $i,
        ] : [],
        'field_oe_media' => $i % 2 !== 0 ? $image_media : $av_photo_media,
      ]);
      $item->save();
      $item->addTranslation('bg', [
        'field_oe_title' => 'BG Item ' . $i,
        'field_oe_text' => $i % 2 === 0 ? 'BG Item description ' . $i : '',
        'field_oe_link' => $i % 2 === 0 ? [
          'uri' => 'http://www.example.com/',
          'title' => 'BG CTA ' . $i,
        ] : [],
      ])->save();
      $items[$i] = $item;
    }
    // Create a Carousel paragraph with Bulgarian translation.
    $paragraph = Paragraph::create([
      'type' => 'oe_carousel',
      'field_oe_carousel_items' => $items,
    ]);
    $paragraph->save();
    $paragraph->addTranslation('bg', $paragraph->toArray())->save();

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    // Assert paragraph rendering for English version.
    $html = $this->renderParagraph($paragraph);
    $assert = new CarouselPatternAssert();
    $expected_values = [
      'items' => [
        [
          'caption_title' => 'Item 1',
          'image' => sprintf(
            '<img src="%s" alt="First image en" class="d-block w-100">',
            $file_url_generator->generateAbsoluteString($en_image->getFileUri())
          ),
        ],
        [
          'caption_title' => 'Item 2',
          'caption' => 'Item description 2',
          'link' => [
            'label' => 'CTA 2',
            'path' => 'http://www.example.com/',
          ],
          'image' => '<img src="//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg" alt="Euro with miniature figurines" class="d-block w-100">',
        ],
        [
          'caption_title' => 'Item 3',
          'image' => sprintf(
            '<img src="%s" alt="First image en" class="d-block w-100">',
            $file_url_generator->generateAbsoluteString($en_image->getFileUri())
          ),
        ],
        [
          'caption_title' => 'Item 4',
          'caption' => 'Item description 4',
          'link' => [
            'label' => 'CTA 4',
            'path' => '/',
          ],
          'image' => '<img src="//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg" alt="Euro with miniature figurines" class="d-block w-100">',
        ],
      ],
    ];
    $assert->assertPattern($expected_values, $html);

    // The caption texts are wrapped by an extra <p> tag which is not tested
    // by the carousel pattern.
    $crawler = new Crawler($html);
    $slides = $crawler->filter('.carousel .carousel-inner .carousel-item');
    $this->assertEquals('Item description 2', $slides->eq(1)->filter('.carousel-caption p')->html());
    $this->assertEquals('Item description 4', $slides->eq(3)->filter('.carousel-caption p')->html());

    // Assert paragraph rendering for Bulgarian version.
    $html = $this->renderParagraph($paragraph, 'bg');
    $expected_values = [
      'items' => [
        [
          'caption_title' => 'BG Item 1',
          'image' => sprintf(
            '<img src="%s" alt="First image bg" class="d-block w-100">',
            $file_url_generator->generateAbsoluteString($bg_image->getFileUri())
          ),
        ],
        [
          'caption_title' => 'BG Item 2',
          'caption' => 'BG Item description 2',
          'link' => [
            'label' => 'BG CTA 2',
            'path' => 'http://www.example.com/',
          ],
          'image' => '<img src="//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg" alt="AV Portal photo bg" class="d-block w-100">',
        ],
        [
          'caption_title' => 'BG Item 3',
          'image' => sprintf(
            '<img src="%s" alt="First image bg" class="d-block w-100">',
            $file_url_generator->generateAbsoluteString($bg_image->getFileUri())
          ),
        ],
        [
          'caption_title' => 'BG Item 4',
          'caption' => 'BG Item description 4',
          'link' => [
            'label' => 'BG CTA 4',
            'path' => 'http://www.example.com/',
          ],
          'image' => '<img src="//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg" alt="AV Portal photo bg" class="d-block w-100">',
        ],
      ],
    ];

    $assert->assertPattern($expected_values, $html);
  }

}
