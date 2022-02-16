<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Symfony\Component\DomCrawler\Crawler;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that the Event content type renders correctly.
 *
 * @group batch1
 */
class ContentEventRenderTest extends ContentRenderTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'oe_whitelabel_helper',
    'oe_starter_content_event',
    'oe_whitelabel_event',
  ];
  
  /**
   * A node to be rendered in diferent display views.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a sample media entity to be embedded.
    $image_file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $media_image = Media::create([
      'bundle' => 'image',
      'name' => 'Starter Image test',
      'oe_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'Starter Image test alt',
          'title' => 'Starter Image test title',
        ],
      ],
    ]);
    $media_image->save();
    
    // // Create a sample document media entity to be embedded.
    // $text_file = File::create([
    //   'uri' => $this->getTestFiles('text')[0]->uri,
    // ])->save();
    // $media_file = Media::create([
    //   'bundle' => 'document',
    //   'name' => 'Starter Image test',
    //   'oe_media_file_type' = 'local',
    //   'oe_media_file' => [
    //     [
    //       'target_id' => $text_file->id(),
    //       'alt' => 'Starter Image test alt',
    //       'title' => 'Starter Image test title',
    //     ],
    //   ],
    // ]);
    // $media_file->save();

    // Create a Event node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getStorage('node')->create([
      'type' => 'oe_event',
      'title' => 'Test event node',
      'oe_summary' => 'http://www.example.org is a web page',
      'body' => 'Event body',
      'oe_event_dates' => [
        'value' => '2022-02-09T20:00:00',
        'end_value' => '2022-02-09T22:00:00',
      ],
      'uid' => 1,
      'status' => 1,
    ]);
    // $node->set('oe_documents', [$media_file]);
    $node->set('oe_featured_media', [$media_image]);
    $node->save();
    $this->node = $node;
  }

  /**
   * Tests that the Event page renders correctly in full display.
   */
  public function testEventRenderingFull(): void {
    $this->drupalGet($this->node->toUrl());

    // Build node full view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'full');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Assert content banner image.
    $picture = $this->assertSession()->elementExists('css', 'img.card-img-top');
    $image = $this->assertSession()->elementExists('css', 'img.rounded-1', $picture);
    $this->assertStringContainsString('image-test.png', $image->getAttribute('src'));
    $this->assertEquals('Starter Image test alt', $image->getAttribute('alt'));

    // Assert content banner title.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertEquals(
      'Test event node',
      trim($content_banner->filter('.card-title')->text())
    );
    // Assert content banner summary.
    $this->assertEquals(
      'http://www.example.org is a web page',
      trim($content_banner->filter('.oe-event__oe-summary')->text())
    );
  }

  /**
   * Tests that the Event page renders correctly in teaser display.
   */
  public function testEventRenderingTeaser(): void {

    $this->drupalGet($this->node->toUrl());

    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'teaser');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Assert content banner image.
    $picture = $this->assertSession()->elementExists('css', 'img.card-img-top');
    $image = $this->assertSession()->elementExists('css', 'img.rounded-1', $picture);
    $this->assertStringContainsString('image-test.png', $image->getAttribute('src'));
    $this->assertEquals('Starter Image test alt', $image->getAttribute('alt'));

    // Assert content banner title.
    $this->assertEquals(
      'Test event node',
      trim($crawler->filter('h5.card-title')->text())
    );
  }

}
