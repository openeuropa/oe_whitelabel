<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;
use Drupal\user\Entity\Role;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests Carousel V2 configuration, data preparation and SDC rendering.
 *
 * @group oe_whitelabel_paragraphs
 */
class CarouselV2Test extends ParagraphsTestBase {

  use MediaCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('media');
    $this->installConfig(['content_translation', 'media', 'media_avportal', 'oe_media', 'oe_media_avportal']);
    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
    $this->createMediaCopyrightField(['image', 'av_portal_photo']);
    $this->container->get('router.builder')->rebuild();
    $path = $this->container->get('extension.list.theme')->getPath('oe_whitelabel');
    require_once $path . '/includes/paragraphs.inc';
  }

  /**
   * Tests the required layout and reused item constraint.
   */
  public function testConfiguration(): void {
    $layout = FieldConfig::load('paragraph.oe_carousel_v2.oe_w_carousel_layout');
    $this->assertTrue($layout->isRequired());
    $this->assertSame(['split' => 'Image alongside content', 'full_width' => 'Full-width image'], $layout->getSetting('allowed_values'));
    $items = FieldConfig::load('paragraph.oe_carousel_v2.field_oe_carousel_items');
    $this->assertSame(['oe_carousel_item' => 'oe_carousel_item'], $items->getSetting('handler_settings')['target_bundles']);
    $paragraph = Paragraph::create(['type' => 'oe_carousel_v2']);
    $paragraph->set('field_oe_carousel_items', [Paragraph::create(['type' => 'oe_carousel_item'])]);
    $violations = $paragraph->get('field_oe_carousel_items')->validate();
    $this->assertStringContainsString('at least 2 items', (string) $violations);
    $paragraph->get('field_oe_carousel_items')->appendItem(Paragraph::create(['type' => 'oe_carousel_item']));
    $this->assertCount(0, $paragraph->get('field_oe_carousel_items')->validate());
    $paragraph->set('oe_w_carousel_layout', NULL);
    $this->assertGreaterThan(0, $paragraph->get('oe_w_carousel_layout')->validate()->count());
    $paragraph->set('oe_w_carousel_layout', 'invalid');
    $this->assertGreaterThan(0, $paragraph->get('oe_w_carousel_layout')->validate()->count());
  }

  /**
   * Tests translated data, URL handling and cache dependencies.
   */
  public function testPreprocess(): void {
    ConfigurableLanguage::createFromLangcode('bg')->save();
    $this->container->get('content_translation.manager')->setEnabled('media', 'image', TRUE);
    FieldConfig::load('media.image.oe_media_image')->setTranslatable(TRUE)->save();
    $image = $this->createImageMedia(['field_media_copyright' => 'Credit EN']);
    $image->addTranslation('bg', [
      'name' => 'Image BG',
      'field_media_copyright' => 'Credit BG',
      'oe_media_image' => [
        'target_id' => $image->get('oe_media_image')->target_id,
        'alt' => 'Alt BG',
        'width' => 1200,
        'height' => 600,
      ],
    ])->save();
    $item = Paragraph::create([
      'type' => 'oe_carousel_item',
      'field_oe_media' => $image,
      'field_oe_title' => 'Title EN',
      'field_oe_text' => 'Caption EN',
      'field_oe_link' => ['uri' => 'route:<front>', 'title' => 'Home EN'],
    ]);
    $item->save();
    $item->addTranslation('bg', [
      'field_oe_title' => 'Title BG',
      'field_oe_text' => 'Caption BG',
      'field_oe_link' => [
        'uri' => 'https://example.com/bg',
        'title' => 'Home BG',
        'options' => ['attributes' => ['class' => ['carousel-link'], 'rel' => 'nofollow']],
      ],
    ])->save();
    $paragraph = Paragraph::create(['type' => 'oe_carousel_v2', 'field_oe_carousel_items' => [$item, $item]]);
    $paragraph->save();
    $bg = $paragraph->addTranslation('bg', $paragraph->toArray());
    $bg->save();
    foreach (['en' => $paragraph, 'bg' => $bg] as $langcode => $translation) {
      $variables = $this->preprocess($translation);
      $suffix = strtoupper($langcode);
      $this->assertCount(2, $variables['slides']);
      $this->assertSame('Title ' . $suffix, $variables['slides'][0]['caption_title']);
      $this->assertSame('Caption ' . $suffix, $variables['slides'][0]['caption']);
      $this->assertSame('Credit ' . $suffix, $variables['slides'][0]['copyright']);
      $this->assertSame($langcode === 'bg' ? 'Alt BG' : 'Alt text', $variables['slides'][0]['image']['alt']);
      if ($langcode === 'bg') {
        $this->assertSame('1200', $variables['slides'][0]['image']['width']);
        $this->assertSame('600', $variables['slides'][0]['image']['height']);
        $this->assertSame([
          'class' => ['carousel-link'],
          'rel' => 'nofollow',
        ], $variables['slides'][0]['link']['attributes']);
      }
      else {
        $this->assertArrayNotHasKey('attributes', $variables['slides'][0]['link']);
      }
      $this->assertSame('Home ' . $suffix, $variables['slides'][0]['link']['label']);
      $this->assertSame($langcode === 'bg' ? 'https://example.com/bg' : '/', $variables['slides'][0]['link']['path']);
      $cache = CacheableMetadata::createFromRenderArray($variables);
      foreach ([$paragraph, $item, $image, $image->get('oe_media_image')->entity] as $entity) {
        foreach ($entity->getCacheTags() as $tag) {
          $this->assertContains($tag, $cache->getCacheTags());
        }
      }
      $this->assertContains('languages:language_content', $cache->getCacheContexts());
    }
  }

  /**
   * Tests that shared preparation preserves the V1 slide representation.
   */
  public function testLegacyPreprocess(): void {
    $image = $this->createImageMedia(['field_media_copyright' => 'Image credit']);
    $item = Paragraph::create([
      'type' => 'oe_carousel_item',
      'field_oe_media' => $image,
      'field_oe_title' => 'Slide title',
      'field_oe_text' => 'Slide caption',
      'field_oe_link' => ['uri' => 'https://example.com/', 'title' => 'Read more'],
    ]);
    $paragraph = Paragraph::create(['type' => 'oe_carousel', 'field_oe_carousel_items' => [$item]]);
    $variables = [
      'paragraph' => $paragraph,
      'user' => $this->container->get('entity_type.manager')->getStorage('user')->load(1),
    ];
    oe_whitelabel_preprocess_paragraph__oe_carousel($variables);
    $this->assertCount(1, $variables['slides']);
    $slide = $variables['slides'][0];
    $this->assertSame('Slide title', $slide['caption_title']);
    $this->assertSame('Slide caption', $slide['caption']);
    $this->assertSame('Image credit', $slide['copyright']);
    $this->assertSame([
      'src' => $this->container->get('file_url_generator')->generateAbsoluteString($image->get('oe_media_image')->entity->getFileUri()),
      'alt' => $image->label(),
    ], $slide['image']);
    $this->assertInstanceOf(Url::class, $slide['link']['path']);
    $this->assertSame('https://example.com/', $slide['link']['path']->toString());
    $this->assertSame('Read more', $slide['link']['label']);
    $this->assertArrayNotHasKey('attributes', $slide['link']);
    $this->assertArrayNotHasKey('layout', $variables);
  }

  /**
   * Tests denied media, unsupported sources and missing references.
   */
  public function testUnavailableMedia(): void {
    $image = $this->createImageMedia(['status' => FALSE]);
    $missing_file = $this->createImageMedia();
    $missing_file->get('oe_media_image')->entity->delete();
    $items = [];
    foreach ([$image, $missing_file, NULL, $this->createAvPortalVideoMedia()] as $media) {
      $item = Paragraph::create(['type' => 'oe_carousel_item', 'field_oe_media' => $media]);
      $item->save();
      $items[] = $item;
    }
    $paragraph = Paragraph::create(['type' => 'oe_carousel_v2', 'field_oe_carousel_items' => $items]);
    $paragraph->save();
    $variables = $this->preprocess($paragraph, TRUE);
    $this->assertSame([], $variables['slides']);
    $cache = CacheableMetadata::createFromRenderArray($variables);
    $this->assertContains('media:' . $image->id(), $cache->getCacheTags());
    $this->assertContains('paragraph:' . $items[2]->id(), $cache->getCacheTags());
    $this->assertNotEmpty($cache->getCacheContexts());
    // An administrator can view the unpublished image, but missing files,
    // missing media and unsupported video sources must still be omitted.
    $this->assertCount(1, $this->preprocess($paragraph)['slides']);
  }

  /**
   * Tests inaccessible links and explicitly decorative image alt text.
   */
  public function testLinkAccess(): void {
    Role::load('anonymous')->grantPermission('view media')->save();
    $image = $this->createImageMedia(['oe_media_image' => ['alt' => '']]);
    $item = Paragraph::create([
      'type' => 'oe_carousel_item',
      'field_oe_media' => $image,
      'field_oe_link' => ['uri' => 'route:system.admin', 'title' => 'Administration'],
    ]);
    $item->save();
    $paragraph = Paragraph::create(['type' => 'oe_carousel_v2', 'field_oe_carousel_items' => [$item]]);
    $variables = $this->preprocess($paragraph, TRUE);
    $this->assertCount(1, $variables['slides']);
    $this->assertArrayNotHasKey('link', $variables['slides'][0]);
    $this->assertSame('', $variables['slides'][0]['image']['alt']);
    $this->assertContains('user.permissions', $variables['#cache']['contexts']);
  }

  /**
   * Tests that the update is repeatable and preserves existing configuration.
   */
  public function testUpdate(): void {
    $v1 = $this->config('core.entity_view_display.paragraph.oe_carousel.default')->getRawData();
    FieldConfig::load('paragraph.oe_carousel_v2.oe_w_carousel_layout')->setLabel('Custom layout label')->save();
    EntityViewDisplay::load('paragraph.oe_carousel_v2.default')->delete();
    $path = $this->container->get('extension.list.module')->getPath('oe_whitelabel_paragraphs');
    require_once $path . '/oe_whitelabel_paragraphs.post_update.php';
    oe_whitelabel_paragraphs_post_update_00004();
    oe_whitelabel_paragraphs_post_update_00004();
    $this->assertNotNull(EntityViewDisplay::load('paragraph.oe_carousel_v2.default'));
    $this->assertSame('Custom layout label', FieldConfig::load('paragraph.oe_carousel_v2.oe_w_carousel_layout')->label());
    $this->assertSame($v1, $this->config('core.entity_view_display.paragraph.oe_carousel.default')->getRawData());
  }

  /**
   * Tests both layouts against the actual OE BT SDC.
   */
  public function testRendering(): void {
    $image = $this->createImageMedia(['field_media_copyright' => 'Image credit']);
    $photo = $this->createAvPortalPhotoMedia(['field_media_copyright' => 'AV credit']);
    $items = [];
    foreach ([$image, $photo] as $index => $media) {
      $item = Paragraph::create([
        'type' => 'oe_carousel_item',
        'field_oe_media' => $media,
        'field_oe_title' => 'Slide ' . $index,
        'field_oe_text' => '<script>alert(1)</script>',
        'field_oe_link' => ['uri' => 'https://example.com/', 'title' => 'Read more'],
      ]);
      $item->save();
      $items[] = $item;
    }
    foreach (['split', 'full_width'] as $layout) {
      $paragraph = Paragraph::create([
        'type' => 'oe_carousel_v2',
        'oe_w_carousel_layout' => $layout,
        'field_oe_carousel_items' => $items,
      ]);
      $paragraph->save();
      $crawler = new Crawler($this->renderParagraph($paragraph));
      $this->assertCount(1, $crawler->filter('.bcl-carousel-v2--' . $layout));
      $this->assertCount(2, $crawler->filter('.carousel-item'));
      $this->assertCount(2, $crawler->filter('.carousel-item img'));
      $this->assertCount(2, $crawler->filter('.carousel-item a[href="https://example.com/"]'));
      $this->assertCount(0, $crawler->filter('.carousel-item script'));
      $this->assertStringContainsString('Image credit', $crawler->text());
      $this->assertStringContainsString('AV credit', $crawler->text());
    }
  }

  /**
   * Calls preprocessing independently of the upstream component template.
   */
  protected function preprocess(Paragraph $paragraph, bool $anonymous = FALSE): array {
    $variables = [
      'paragraph' => $paragraph,
      'user' => $anonymous ? new AnonymousUserSession() : $this->container->get('entity_type.manager')->getStorage('user')->load(1),
    ];
    oe_whitelabel_preprocess_paragraph__oe_carousel_v2($variables);
    return $variables;
  }

}
