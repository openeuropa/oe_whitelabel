<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Functional;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\GalleryPatternAssert;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;
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
    'oe_paragraphs_gallery',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * Tests the paragraph rendering.
   */
  public function testRendering(): void {
    $image = $this->createImageMedia();
    $avportal_photo = $this->createAvPortalPhotoMedia();
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

    $file_url_generator = \Drupal::service('file_url_generator');
    // The attribute order is different from 9.4.0 onwards.
    $attribute_pre_940 = version_compare(\Drupal::VERSION, '9.4.0', '<') ? '' : 'loading="lazy" ';
    $attribute_940 = version_compare(\Drupal::VERSION, '9.4.0', '>=') ? '' : 'loading="lazy" ';
    $expected_items = [
      [
        'thumbnail' => [
          'caption_title' => 'Image title',
          'rendered' => sprintf(
            '<img %ssrc="%s" width="200" height="89" alt="Alt text" %sclass="img-fluid">',
            $attribute_pre_940,
            $file_url_generator->generate($image->get('oe_media_image')->entity->getFileUri())->toString(),
            $attribute_940
          ),
        ],
        'media' => [
          'caption_title' => 'Image title',
          'rendered' => sprintf(
            '<img %sdata-src="%s" width="200" height="89" alt="Alt text" %sclass="img-fluid">',
            $attribute_pre_940,
            $file_url_generator->generate($image->get('oe_media_image')->entity->getFileUri())->toString(),
            $attribute_940
          ),
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Euro with miniature figurines',
          'rendered' => sprintf(
            '<img %ssrc="%s" width="639" height="426" alt="Euro with miniature figurines" %sclass="img-fluid">',
            $attribute_pre_940,
            $file_url_generator->generate($avportal_photo->get('thumbnail')->entity->getFileUri())->toString(),
            $attribute_940
          ),
        ],
        'media' => [
          'caption_title' => 'Euro with miniature figurines',
          'rendered' => '<img class="avportal-photo img-fluid" alt="Euro with miniature figurines" data-src="//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg">',
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
          'rendered' => sprintf(
            '<img %ssrc="%s" width="352" height="200" alt="" %sclass="img-fluid">',
            $attribute_pre_940,
            $file_url_generator->generate($avportal_video->get('thumbnail')->entity->getFileUri())->toString(),
            $attribute_940
          ),
          'play_icon' => TRUE,
        ],
        'media' => [
          'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
          'rendered' => '<iframe id="videoplayerI-163162" data-src="//ec.europa.eu/avservices/play.cfm?ref=I-163162&amp;lg=EN&amp;sublg=none&amp;autoplay=true&amp;tin=10&amp;tout=59" frameborder="0" allowtransparency allowfullscreen webkitallowfullscreen mozallowfullscreen width="640" height="390" class="media-avportal-content"></iframe>',
        ],
      ],
      [
        'thumbnail' => [
          'caption_title' => 'Energy, let\'s save it!',
          'rendered' => sprintf(
            '<img %ssrc="%s" width="480" height="360" alt="" %sclass="img-fluid">',
            $attribute_pre_940,
            $file_url_generator->generate($video->get('thumbnail')->entity->getFileUri())->toString(),
            $attribute_940
          ),
          'play_icon' => TRUE,
        ],
        'media' => [
          'caption_title' => 'Energy, let\'s save it!',
          'rendered' => sprintf(
            '<iframe data-src="/build/media/oembed?url=https%%3A//www.youtube.com/watch%%3Fv%%3D1-g73ty9v04&amp;max_width=0&amp;max_height=0&amp;hash=%s" frameborder="0" allowtransparency width="200" height="150" class="media-oembed-content" title="Energy, let\'s save it!"></iframe>',
            \Drupal::service('media.oembed.iframe_url_helper')->getHash('https://www.youtube.com/watch?v=1-g73ty9v04', 0, 0)
          ),
        ],
      ],
    ];
    $this->assertParagraphRendering([
      'items' => $expected_items,
    ], $paragraph);

    // Add a title.
    $paragraph->set('field_oe_title', 'Gallery paragraph title')->save();
    $this->assertParagraphRendering([
      'title' => 'Gallery paragraph title',
      'items' => $expected_items,
    ], $paragraph);

    // Set also a description.
    $description = $this->getRandomGenerator()->sentences(20);
    $paragraph->set('field_oe_plain_text_long', $description)->save();
    $this->assertParagraphRendering([
      'title' => 'Gallery paragraph title',
      'description' => $description,
      'items' => $expected_items,
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
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph being rendered.
   */
  protected function assertParagraphRendering(array $expected, ParagraphInterface $paragraph): void {
    $expected += [
      'title' => NULL,
      'description' => NULL,
      'items' => [],
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
