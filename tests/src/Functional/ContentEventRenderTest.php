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
 */
class ContentEventRenderTest extends WhitelabelBrowserTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_whitelabel_event',
  ];

  /**
   * A node to be rendered in diferent display views.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a sample media entity to be embedded.
    File::create([
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

    // Create a sample document media entity to be embedded.
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    $media_document = Media::create([
      'bundle' => 'document',
      'name' => 'Event document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => 2,
          'alt' => 'Event document alt',
          'title' => 'Event document title',
        ],
      ],
    ]);
    $media_document->save();

    // Create a News node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
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
    $node->set('oe_documents', [$media_document]);
    $node->set('oe_featured_media', [$media_image]);
    $node->save();
    $this->node = $node;
  }

  /**
   * Tests that the Event page renders correctly in full display.
   */
  public function testEventRenderingFull(): void {
    // Build node full view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'full');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    // Assert content banner title.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertEquals(
      'Test event node',
      trim($content_banner->filter('.card-title')->text())
    );
    // Assert content banner image.
    $image = $content_banner->filter('img');
    $this->assertCount(1, $image);
    $this->assertCount(1, $image->filter('.card-img-top'));
    $this->assertStringContainsString(
      'image-test.png',
      trim($image->attr('src'))
    );
    $this->assertEquals('Starter Image test alt',
      $image->attr('alt')
    );
    // Assert content banner summary.
    $this->assertEquals(
      'http://www.example.org is a web page',
      trim($content_banner->filter('.oe-event__oe-summary')->text())
    );
    // Assert inpage-navigation.
    $this->assertEquals(
      'Page content',
      trim($crawler->filter('nav.bcl-inpage-navigation > h5')->text())
    );
    $inpage_links = $crawler->filter('nav.bcl-inpage-navigation > ul');
    $this->assertCount(1, $inpage_links->filter('li'));
    // Assert inpage-navigation first link.
    $this->assertEquals(
      'Content',
      trim($inpage_links->filter('li:first-of-type')->text())
    );

  }

  /**
   * Tests that the Event page renders correctly in teaser display.
   */
  public function testEventRenderingTeaser(): void {
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'teaser');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    // Assert content banner title.
    $this->assertEquals(
      'Test event node',
      trim($crawler->filter('h5.card-title')->text())
    );
    // Assert content banner image.
    $image = $crawler->filter('img');
    $this->assertCount(1, $image);
    $this->assertCount(1, $image->filter('.card-img-top'));
    $this->assertStringContainsString(
      'image-test.png',
      trim($image->attr('src'))
    );
  }

}
