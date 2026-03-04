<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\GalleryPatternAssert;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the gallery paragraph.
 *
 * A browser test was used, instead of a kernel test, because of the optional
 * configurations that are installed only when oe_paragraphs_gallery is
 * installed. This requires a precise order of installation in a kernel test,
 * and the added complexity doesn't bring any value.
 */
class GalleryParagraphTest extends BrowserTestBase {

  use MediaCreationTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media_oembed_mock',
    'oe_paragraphs_gallery',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installMediaCopyrightField(['image', 'av_portal_photo']);
  }

  /**
   * Tests the paragraph rendering.
   */
  public function testRendering(): void {
    $image = $this->createImageMedia([
      'field_media_copyright' => 'Image copyright',
    ]);
    $avportal_photo = $this->createAvPortalPhotoMedia([
      'field_media_copyright' => 'AV Portal photo copyright',
    ]);
    $avportal_video = $this->createAvPortalVideoMedia();
    $video = $this->createRemoteVideoMedia();

    $paragraph = Paragraph::create([
      'type' => 'oe_gallery',
      'field_oe_gallery_items' => [
        $image,
        $avportal_photo,
        $avportal_video,
        $video,
      ],
    ]);
    $paragraph->save();

    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $image = $media_storage->load($image->id());
    $avportal_photo = $media_storage->load($avportal_photo->id());
    $avportal_video = $media_storage->load($avportal_video->id());
    $video = $media_storage->load($video->id());

    $file_url_generator = \Drupal::service('file_url_generator');
    $fn_get_file_url = static fn($entity, $field) => $file_url_generator->generate($entity->get($field)->entity->getFileUri())->toString();
    $image_src = $fn_get_file_url($image, 'oe_media_image');
    $avportal_photo_thumb_src = $fn_get_file_url($avportal_photo, 'thumbnail');
    $avportal_video_thumb_src = $fn_get_file_url($avportal_video, 'thumbnail');
    $video_thumb_src = $fn_get_file_url($video, 'thumbnail');
    foreach ([$image_src, $avportal_photo_thumb_src, $avportal_video_thumb_src, $video_thumb_src] as $src) {
      $this->assertNotSame('', $src);
      $this->assertNotFalse(parse_url($src));
    }
    [$image_width, $image_height] = $this->getImageDimensions($image, 'oe_media_image');
    [$avportal_photo_width, $avportal_photo_height] = $this->getImageDimensions($avportal_photo, 'thumbnail');
    [$avportal_video_width, $avportal_video_height] = $this->getImageDimensions($avportal_video, 'thumbnail');
    [$video_thumb_width, $video_thumb_height] = $this->getImageDimensions($video, 'thumbnail');
    $avportal_photo_url = \Drupal::config('media_avportal.settings')->get('photos_base_uri')
      . $avportal_photo->getSource()->getMetadata($avportal_photo, 'photo_uri');
    $expected_items = [
      [
        'thumbnail' => [
          'caption_title' => 'Image title',
          'rendered' => sprintf(
            '<img loading="lazy" src="%s" width="%d" height="%d" alt="Alt text" class="img-fluid">',
            $image_src,
            $image_width,
            $image_height
          ),
        ],
        'media' => [
          'caption_title' => 'Image title',
          'rendered' => sprintf(
            '<img loading="lazy" data-src="%s" width="%d" height="%d" alt="Alt text" class="img-fluid">',
            $image_src,
            $image_width,
            $image_height
          ),
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Euro with miniature figurines',
          'rendered' => sprintf(
            '<img loading="lazy" src="%s" width="%d" height="%d" alt="Euro with miniature figurines" class="img-fluid">',
            $avportal_photo_thumb_src,
            $avportal_photo_width,
            $avportal_photo_height
          ),
        ],
        'media' => [
          'caption_title' => 'Euro with miniature figurines',
          'rendered' => sprintf(
            '<img class="avportal-photo img-fluid" alt="Euro with miniature figurines" data-src="%s">',
            $avportal_photo_url
          ),
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
          'rendered' => sprintf(
            '<img loading="lazy" src="%s" width="%d" height="%d" alt="" class="img-fluid">',
            $avportal_video_thumb_src,
            $avportal_video_width,
            $avportal_video_height
          ),
          'play_icon' => TRUE,
        ],
        'media' => [
          'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
          'rendered' => '<iframe id="videoplayerI-163162" data-src="https://audiovisual.ec.europa.eu/corporateplayer/index.html?ref=I-163162&amp;lg=EN&amp;sublg=none&amp;autoplay=true" frameborder="0" allowtransparency allowfullscreen webkitallowfullscreen mozallowfullscreen width="640" height="390" class="media-avportal-content" title=" Economic and Financial Affairs Council - Arrivals"></iframe>',
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Energy, let\'s save it!',
          'rendered' => sprintf(
            '<img loading="lazy" src="%s" width="%d" height="%d" alt="" class="img-fluid">',
            $video_thumb_src,
            $video_thumb_width,
            $video_thumb_height
          ),
          'play_icon' => TRUE,
        ],
        'media' => [
          'caption_title' => 'Energy, let\'s save it!',
          'rendered' => sprintf(
            // Use mock youtube url from oe_media_oembed_mock module.
            '<iframe data-src="%s?url=https%%3A//www.youtube.com/watch%%3Fv%%3D1-g73ty9v04&amp;max_width=0&amp;max_height=0&amp;hash=%s" width="459" height="344" class="media-oembed-content" loading="eager" title="Energy, let\'s save it!"></iframe>',
            Url::fromRoute('media.oembed_iframe')->setAbsolute()->toString(),
            \Drupal::service('media.oembed.iframe_url_helper')->getHash('https://www.youtube.com/watch?v=1-g73ty9v04', 0, 0)
          ),
        ],
      ],
    ];
    $this->assertParagraphRendering([
      'items' => $expected_items,
      'copyrights' => [
        'Image copyright',
        'AV Portal photo copyright',
      ],
    ], $paragraph);

    // Add a title.
    $paragraph->set('field_oe_title', 'Gallery paragraph title')->save();
    $this->assertParagraphRendering([
      'title' => 'Gallery paragraph title',
      'items' => $expected_items,
      'copyrights' => [
        'Image copyright',
        'AV Portal photo copyright',
      ],
    ], $paragraph);

    // Set also a description.
    $description = $this->getRandomGenerator()->sentences(20);
    $paragraph->set('field_oe_plain_text_long', $description)->save();
    $this->assertParagraphRendering([
      'title' => 'Gallery paragraph title',
      'description' => $description,
      'items' => $expected_items,
      'copyrights' => [
        'Image copyright',
        'AV Portal photo copyright',
      ],
    ], $paragraph);
  }

  /**
   * Asserts the gallery paragraph rendering.
   *
   * @param array $expected
   *   A list of expected values:
   *   - title: the gallery title.
   *   - description: the gallery description.
   *   - items: the gallery items in a format suitable for GalleryPatternAssert.
   *   - copyrights: expected copyright values in gallery slides.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph being rendered.
   */
  protected function assertParagraphRendering(array $expected, ParagraphInterface $paragraph): void {
    $expected += [
      'title' => NULL,
      'description' => NULL,
      'items' => [],
      'copyrights' => [],
    ];

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $this->assertCount(1, $crawler->filter('body > div.paragraph--type--oe-gallery'));
    $this->assertCount(1, $crawler->filter('div.paragraph--type--oe-gallery'));

    if ($expected['title'] !== NULL) {
      $this->assertCount(1, $crawler->filter('div.paragraph--type--oe-gallery > h2'));
      $this->assertEquals($expected['title'], trim($crawler->filter('div.paragraph--type--oe-gallery > h2.mb-4.bcl-heading')->text()));
    }
    else {
      $this->assertCount(0, $crawler->filter('div.paragraph--type--oe-gallery > h2'));
    }

    if ($expected['description'] !== NULL) {
      $this->assertCount(1, $crawler->filter('div.paragraph--type--oe-gallery > p'));
      $this->assertEquals($expected['description'], trim($crawler->filter('div.paragraph--type--oe-gallery > p')->text()));
    }
    else {
      $this->assertCount(0, $crawler->filter('div.paragraph--type--oe-gallery > p'));
    }

    $gallery_element = $crawler->filter('div.paragraph--type--oe-gallery > div.bcl-gallery');
    $this->assertCount(1, $gallery_element);
    $assert = new GalleryPatternAssert();
    $assert->assertPattern([
      'title' => NULL,
      'items' => $expected['items'],
    ], $gallery_element->outerHtml());

    $copyright_elements = $gallery_element->filter('.bcl-copyright--overlay');
    $this->assertCount(count($expected['copyrights']), $copyright_elements);
    foreach ($expected['copyrights'] as $index => $copyright) {
      $this->assertEquals($copyright, trim($copyright_elements->eq($index)->text()));
    }
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

    return (string) $this->container->get('renderer')->renderRoot($render);
  }

  /**
   * Creates the media copyright field and attaches it to media bundles.
   *
   * @param string[] $bundles
   *   The media bundles.
   */
  protected function installMediaCopyrightField(array $bundles): void {
    if (!FieldStorageConfig::loadByName('media', 'field_media_copyright')) {
      FieldStorageConfig::create([
        'field_name' => 'field_media_copyright',
        'entity_type' => 'media',
        'type' => 'string',
      ])->save();
    }

    foreach ($bundles as $bundle) {
      if (!FieldConfig::loadByName('media', $bundle, 'field_media_copyright')) {
        FieldConfig::create([
          'field_name' => 'field_media_copyright',
          'entity_type' => 'media',
          'bundle' => $bundle,
          'label' => 'Copyright',
        ])->save();
      }

      $gallery_display = EntityViewDisplay::load("media.$bundle.oe_w_pattern_gallery_item");
      if ($gallery_display) {
        $gallery_display->setComponent('field_media_copyright', [
          'type' => 'string',
          'label' => 'visually_hidden',
          'settings' => [
            'link_to_entity' => FALSE,
          ],
          'third_party_settings' => [
            'oe_whitelabel_helper' => [
              'pattern_mapping' => 'copyright',
            ],
          ],
          'weight' => 3,
          'region' => 'content',
        ])->save();
      }
    }
  }

}
