<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Symfony\Component\DomCrawler\Crawler;

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
    'block',
    'oe_whitelabel_starter_event',
  ];

  /**
   * Creates an example event node.
   *
   * @return \Drupal\node\NodeInterface
   *   Event node.
   */
  protected function createExampleEvent(): NodeInterface {
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

    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'oe_sc_event',
        'title' => 'Test event node',
        'oe_summary' => 'https://www.example.org is a web page',
        'body' => 'Event body',
        'oe_sc_event_dates' => [
          'value' => '2022-02-09T20:00:00',
          'end_value' => '2022-02-09T22:00:00',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->set('oe_documents', [$media_document]);
    $node->set('oe_featured_media', [$media_image]);
    $node->save();
    return $node;
  }

  /**
   * Tests the event page.
   */
  public function testEventPage(): void {
    $node = $this->createExampleEvent();
    $this->drupalGet('node/' . $node->id());

    /** @var \Symfony\Component\BrowserKit\AbstractBrowser $client */
    $client = $this->getSession()->getDriver()->getClient();
    $crawler = $client->getCrawler();

    // Select the content banner element.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertCount(1, $content_banner);

    // Assert content banner title.
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
    $this->assertEquals(
      'Starter Image test alt',
      $image->attr('alt')
    );

    // Assert content banner summary.
    $this->assertEquals(
      'https://www.example.org is a web page',
      trim($content_banner->filter('.oe-sc-event__oe-summary')->text())
    );

    // Assert event dates starting and ending same day.
    $this->assertSession()->pageTextMatches(
      '/[a-zA-Z]+\\s[0-9]+\\s[a-zA-Z]+\\s2022,\\s[0-9]*\\.[0-9]+-[0-9]*\\.[0-9]+\\s\\([a-zA-Z]+\\)/i'
      );

    // Assert event dates starting and ending at different days.
    $node->set('oe_sc_event_dates', [
      'value' => '2022-02-15T08:00:00',
      'end_value' => '2022-02-22T18:00:00',
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->pageTextMatches(
      '/[a-zA-Z]+\\s[0-9]+\\s[a-zA-Z]+\\s2022,\\s[0-9]*\\.[0-9]+-[a-zA-Z]+\\s[0-9]+\\s[a-zA-Z]+\\s2022,\\s[0-9]*\\.[0-9]+\\s\\([a-zA-Z]+\\)/i'
    );

    // Assert in-page navigation title.
    $this->assertEquals(
      'Page content',
      trim($crawler->filter('nav.bcl-inpage-navigation > h3')->text())
    );

    // Assert in-page navigation links.
    $inpage_links = $crawler->filter('nav.bcl-inpage-navigation > ul');
    $this->assertCount(2, $inpage_links->filter('li'));
    $this->assertEquals(
      'Content',
      trim($inpage_links->filter('li:nth-of-type(1)')->text())
    );
    $this->assertEquals(
      'Documents',
      trim($inpage_links->filter('li:nth-of-type(2)')->text())
    );

    // Assert body text.
    $this->assertSame(
      'Event body',
      $crawler->filter('#oe-content-body p')->text()
    );

    // Assert inpage_navigation not loaded if there is no body and documents.
    $node->set('oe_documents', NULL);
    $node->set('body', NULL);
    $node->save();

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->elementNotExists('css', 'nav.bcl-inpage-navigation');
  }

  /**
   * Tests the event rendered in 'Teaser' view mode.
   */
  public function testEventRenderingTeaser(): void {
    $node = $this->createExampleEvent();
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node, 'teaser');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $article = $crawler->filter('article');
    $this->assertCount(1, $article);

    $this->assertEquals(
      'Test event node',
      trim($article->filter('h1.card-title')->text())
    );
    $image = $article->filter('img');
    $this->assertCount(1, $image);
    $this->assertCount(1, $image->filter('.card-img-top'));
    $this->assertStringContainsString(
      'image-test.png',
      trim($image->attr('src'))
    );
  }

}
