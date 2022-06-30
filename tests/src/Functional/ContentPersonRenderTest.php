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
 * Tests that the Person content type renders correctly.
 */
class ContentPersonRenderTest extends WhitelabelBrowserTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_starter_person',
  ];

  /**
   * Tests the person page.
   */
  public function testPersonPage(): void {
    $node = $this->createExamplePerson();
    $this->drupalGet($node->toUrl());
    /** @var \Symfony\Component\BrowserKit\AbstractBrowser $client */
    $client = $this->getSession()->getDriver()->getClient();
    $crawler = $client->getCrawler();

    // Select the content banner element.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertCount(1, $content_banner);

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

    // Assert content banner content elements.
    $this->assertEquals(
      'Stefan Mayer',
      trim($content_banner->filter('.card-title')->text())
    );
    $this->assertEquals(
      'Director',
      trim($content_banner->filter('.card-body > div.my-4 > span:nth-child(1)')->text())
    );
    $this->assertEquals(
      'DG Test',
      trim($content_banner->filter('.card-body > div.my-4 > span:nth-child(2)')->text())
    );
    $this->assertEquals(
      'Germany',
      trim($content_banner->filter('.card-body > div.my-4 > span:nth-child(3)')->text())
    );
    $this->assertEquals(
      'This field is used to add a short biography of the person.',
      trim($content_banner->filter('p')->text())
    );

    $this->assertEquals(
      'Twitter profile',
      trim($content_banner->filter('div.mb-2 > div.mb-3 > a.standalone')->text())
    );

    $content = $crawler->filter('div.col-12.col-lg-10.col-xl-9.col-xxl-8');

    $this->assertEquals(
      'Additional information',
      trim($content->filter('h2')->eq(0)->text())
    );
    $this->assertEquals(
      'Additional information example field.',
      trim($content->filter('div > p')->text())
    );

    $this->assertEquals(
      'Related documents',
      trim($content->filter('h2')->eq(1)->text())
    );
    $this->assertEquals(
      'Event document test',
      trim($content->filter('.bcl-file p')->text())
    );
  }

  /**
   * Tests the person rendered in 'Teaser' view mode.
   */
  public function testPersonRenderingTeaser(): void {
    $node = $this->createExamplePerson();
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node, 'teaser');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $article = $crawler->filter('article');
    $this->assertCount(1, $article);

    $this->assertEquals(
      'Stefan Mayer',
      trim($article->filter('h1.card-title')->text())
    );
    $image = $article->filter('img');
    $this->assertCount(1, $image);
    $this->assertCount(1, $image->filter('.card-img-top'));
    $this->assertStringContainsString(
      'image-test.png',
      trim($image->attr('src'))
    );

    $this->assertEquals(
      'Director',
      trim($article->filter('.card-body > div.my-3 > span:nth-child(1)')->text())
    );
    $this->assertEquals(
      'DG Test',
      trim($article->filter('.card-body > div.my-3 > span:nth-child(2)')->text())
    );
  }

  /**
   * Creates an example person node.
   *
   * @return \Drupal\node\NodeInterface
   *   Person node.
   */
  protected function createExamplePerson(): NodeInterface {
    // Create a sample image media entity to be embedded.
    $image_file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $image_file->save();
    $media_image = Media::create([
      'bundle' => 'image',
      'name' => 'Starter Image test',
      'oe_media_image' => [
        [
          'target_id' => $image_file->id(),
          'alt' => 'Starter Image test alt',
          'title' => 'Starter Image test title',
        ],
      ],
    ]);
    $media_image->save();

    // Create a sample document media entity to be embedded.
    $document_file = File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ]);
    $document_file->save();
    $media_document = Media::create([
      'bundle' => 'document',
      'name' => 'Event document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => (int) $document_file->id(),
          'alt' => 'Event document alt',
          'title' => 'Event document title',
        ],
      ],
    ]);
    $media_document->save();

    $document_reference = \Drupal::entityTypeManager()
      ->getStorage('oe_document_reference')
      ->create([
        'type' => 'oe_document',
        'oe_document' => $media_document,
        'status' => 1,
      ]);
    $document_reference->save();

    // Create a Person node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'oe_sc_person',
        'oe_sc_person_first_name' => 'Stefan',
        'oe_sc_person_last_name' => 'Mayer',
        'oe_sc_person_country' => 'DE',
        'oe_sc_person_occupation' => 'DG Test',
        'oe_sc_person_position' => 'Director',
        'oe_summary' => 'This field is used to add a short biography of the person.',
        'oe_sc_person_additional_info' => 'Additional information example field.',
        'oe_social_media_links' => [
          [
            'uri' => 'https://twiiter.com',
            'title' => 'Twitter profile',
            'link_type' => 'twitter',
          ],
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->set('oe_sc_person_documents', [$document_reference]);
    $node->set('oe_sc_person_image', [$media_image]);
    $node->save();
    return $node;
  }

}
