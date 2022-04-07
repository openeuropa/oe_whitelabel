<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Symfony\Component\DomCrawler\Crawler;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that the News content type renders correctly.
 */
class ContentNewsRenderTest extends WhitelabelBrowserTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_starter_news',
  ];

  /**
   * A node to be rendered in different display views.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a sample image media entity to be embedded.
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

    // Create a News node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'oe_sc_news',
        'title' => 'Test news node',
        'oe_summary' => 'http://www.example.org is a web page',
        'body' => 'News body',
        'oe_publication_date' => [
          'value' => '2022-02-09',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->set('oe_featured_media', [$media_image]);
    $node->save();
    $this->node = $node;
  }

  /**
   * Tests that the News page renders correctly in full display.
   */
  public function testNewsRenderingFull(): void {
    // Build node full view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'full');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    // Assert content banner title.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertEquals(
      'Test news node',
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
    // Assert content banner publication date.
    $this->assertEquals(
      '09 February 2022',
      trim($content_banner->filter('.card-body > div.my-4')->text())
    );
    // Assert content banner summary.
    $this->assertEquals(
      'http://www.example.org is a web page',
      trim($content_banner->filter('.oe-sc-news__oe-summary')->text())
    );
    // Assert the news content.
    $this->assertEquals(
      'News body',
      trim($crawler->filter('.oe-sc-news__body')->text())
    );
  }

  /**
   * Tests that the News page renders correctly in teaser display.
   */
  public function testNewsRenderingTeaser(): void {
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($this->node, 'teaser');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    // Assert content banner title.
    $this->assertEquals(
      'Test news node',
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
    // Assert content banner content.
    $this->assertEquals(
      'http://www.example.org is a web page',
      trim($crawler->filter('div.card-text')->text())
    );
    // Assert content banner publication date.
    $this->assertEquals(
      '09 February 2022',
      trim($crawler->filter('div.card-body > div > span.text-muted')->text())
    );
  }

}
