<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Test 'AV Media' paragraph.
 */
class AvMediaParagraphsTest extends ParagraphsTestBase {

  use MediaCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media_oembed_mock',
    'oe_paragraphs_av_media',
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
      'media_avportal',
      'oe_media',
      'oe_media_avportal',
      'oe_media_iframe',
      'oe_paragraphs_av_media',
    ]);

    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
  }

  /**
   * Test 'AV Media' paragraph rendering with allowed media sources.
   */
  public function testAvMediaParagraph(): void {
    $media_image = $this->createImageMedia();
    $media_iframe = $this->createVideoIframeMedia();
    $media_remote = $this->createRemoteVideoMedia();
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
    ])->toString();
    $media_av_video = $this->createAvPortalVideoMedia();
    $media_av_photo = $this->createAvPortalPhotoMedia();
    $remote_thumbnail_url_encrypted = Crypt::hashBase64('store2/4/P038924-35966.jpg');

    $scenarios = [
      'image' => [
        'media' => $media_image->id(),
        'expected_src' => '/styles/oe_bootstrap_theme_medium_no_crop/public/example_1.jpeg',
        'selector' => 'img',
      ],
      'iframe' => [
        'media' => $media_iframe->id(),
        'expected_src' => 'https://example.com',
        'selector' => 'iframe',
      ],
      'remote_video' => [
        'media' => $media_remote->id(),
        'expected_src' => $partial_iframe_url,
        'selector' => '.ratio-16x9 iframe',
      ],
      'avportal_video' => [
        'media' => $media_av_video->id(),
        'expected_src' => '//ec.europa.eu/avservices/play.cfm?ref=I-163162',
        'selector' => '.ratio-16x9 iframe',
      ],
      'avportal_photo' => [
        'media' => $media_av_photo->id(),
        'expected_src' => "/styles/oe_bootstrap_theme_medium_no_crop/public/media_avportal_thumbnails/$remote_thumbnail_url_encrypted.jpg",
        'selector' => 'img',
      ],
    ];

    foreach ($scenarios as $name => $values) {
      try {
        $crawler = $this->renderAvMediaParagraph($values['media']);
        $this->assertMediaAddedWithExpectedSource($crawler, $values['expected_src'], $values['selector']);
      }
      catch (\Exception $exception) {
        throw new \Exception(sprintf('Failed assertion for scenario %s.', $name), 0, $exception);
      }
    }
  }

  /**
   * Renders an AV Media paragraph given a media ID.
   *
   * @param int $media_id
   *   The media id.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   The DomCrawler of the rendered paragraph.
   */
  protected function renderAvMediaParagraph(string $media_id): Crawler {
    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_av_media',
      'field_oe_media' => [
        'target_id' => $media_id,
      ],
    ]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    return new Crawler($html);
  }

  /**
   * Assets that the media added is of the expected source.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler of the rendered paragraph.
   * @param string $expected_src
   *   A partial of the expected source of the rendered media.
   * @param string $media_selector
   *   A selector to find the rendered media by inside its container.
   */
  protected function assertMediaAddedWithExpectedSource(Crawler $crawler, string $expected_src, string $media_selector): void {
    $element = $crawler->filter("figure $media_selector");
    self::assertStringContainsString($expected_src, $element->attr('src'));
  }

}
