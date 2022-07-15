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
class PersonContentRenderTest extends WhitelabelBrowserTestBase {

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
    $node = $this->createExamplePersonWithRequiredFieldsOnly();
    $this->drupalGet($node->toUrl());
    /** @var \Symfony\Component\BrowserKit\AbstractBrowser $client */
    $client = $this->getSession()->getDriver()->getClient();
    $crawler = $client->getCrawler();

    // Assert required fields are present.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertCount(1, $content_banner);

    $this->assertEquals(
      'Stefan Mayer',
      $content_banner->filter('.card-title')->text()
    );
    $this->assertEquals(
      'DG Test',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(1)')->text()
    );
    $this->assertEquals(
      'Director',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(2)')->text()
    );
    $this->assertEquals(
      'Germany',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(3)')->text()
    );

    // Assert optional fields are not present.
    // Assert no image is present.
    $this->assertCount(0, $content_banner->filter('img'));

    // Assert short description is not present.
    $this->assertCount(0, $content_banner->filter('p'));

    // Assert no social media links are present.
    $this->assertCount(0, $content_banner->filter('a.standalone'));

    // Assert additional information is not present.
    $this->assertStringNotContainsString(
      'Additional information',
      $crawler->text()
    );

    // Assert documents are not present.
    $this->assertStringNotContainsString(
      'Related documents',
      $crawler->text()
    );

    // Test person with all fields.
    $node_with_all_fields = $this->createExamplePersonWithAllFields();
    $this->drupalGet($node_with_all_fields->toUrl());
    $crawler = $client->getCrawler();

    $content_banner = $crawler->filter('.bcl-content-banner');

    $this->assertEquals(
      'Stefan Mayer',
      $content_banner->filter('.card-title')->text()
    );
    $this->assertEquals(
      'DG Test',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(1)')->text()
    );
    $this->assertEquals(
      'Director',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(2)')->text()
    );
    $this->assertEquals(
      'Germany',
      $content_banner->filter('.card-body > div.my-4 > span:nth-child(3)')->text()
    );

    // Assert content banner image.
    $image = $content_banner->filter('img.card-img-top');
    $this->assertCount(1, $image);
    $this->assertStringContainsString(
      'image-test.png',
      $image->attr('src')
    );
    $this->assertEquals('Starter Image test alt',
      $image->attr('alt')
    );

    $this->assertEquals(
      'This field is used to add a short biography of the person.',
      $content_banner->filter('p')->text()
    );

    $this->assertEquals(
      'Twitter profile',
      $content_banner->filter('div.mb-2 > div.mb-3 > a.standalone')->text()
    );

    $content = $crawler->filter('div.col-12');

    $this->assertEquals(
      'Additional information',
      $content->filter('h2')->eq(0)->text()
    );
    $this->assertEquals(
      'Additional information example field.',
      $content->filter('div > p')->text()
    );

    $this->assertEquals(
      'Related documents',
      $content->filter('h2')->eq(1)->text()
    );

    $document_group_title = $content->filter('h3.fs-4');
    $this->assertEquals('Curriculum Vitae', $document_group_title->text());
    $files = $content->filter('.bcl-file');
    $this->assertEquals(
      'Person document test',
      $files->filter('p')->text()
    );

    $this->assertCount(3, $files);
  }

  /**
   * Tests the person rendered in 'Teaser' view mode.
   */
  public function testPersonRenderingTeaser(): void {
    $node = $this->createExamplePersonWithAllFields();
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node, 'teaser');
    $markup = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $markup);

    $article = $crawler->filter('article');
    $this->assertCount(1, $article);

    $this->assertEquals(
      'Stefan Mayer',
      $article->filter('h1.card-title')->text()
    );
    $image = $article->filter('img.card-img-top');
    $this->assertCount(1, $image);
    $this->assertStringContainsString(
      'image-test.png',
      $image->attr('src')
    );
    $this->assertEquals(
      'DG Test',
      $article->filter('.card-body > div.my-3 > span:nth-child(1)')->text()
    );
    $this->assertEquals(
      'Director',
      $article->filter('.card-body > div.my-3 > span:nth-child(2)')->text()
    );
  }

  /**
   * Creates an example person node only with required fields.
   *
   * @return \Drupal\node\NodeInterface
   *   Person node.
   */
  protected function createExamplePersonWithRequiredFieldsOnly(): NodeInterface {
    // Create a Person node with required fields.
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
        'uid' => 1,
        'status' => 1,
      ]);
    $node->save();
    return $node;
  }

  /**
   * Creates an example person node with required and optional fields.
   *
   * @return \Drupal\node\NodeInterface
   *   Person node.
   */
  protected function createExamplePersonWithAllFields(): NodeInterface {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->createExamplePersonWithRequiredFieldsOnly();
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
      'name' => 'Person document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => (int) $document_file->id(),
          'alt' => 'Person document alt',
          'title' => 'Person document title',
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
    $document_group_reference = \Drupal::entityTypeManager()
      ->getStorage('oe_document_reference')
      ->create([
        'type' => 'oe_document_group',
        'oe_title' => 'Curriculum Vitae',
        'oe_documents' => [$media_document, $media_document],
        'status' => 1,
      ]);
    $document_reference->save();

    $node->set('oe_summary', 'This field is used to add a short biography of the person.');
    $node->set('oe_sc_person_additional_info', 'Additional information example field.');
    $node->set('oe_social_media_links', [
      'uri' => 'https://twitter.com',
      'title' => 'Twitter profile',
      'link_type' => 'twitter',
    ]);
    $node->set(
      'oe_sc_person_documents',
      [$document_reference, $document_group_reference]
    );
    $node->set('oe_sc_person_image', [$media_image]);
    $node->save();
    return $node;
  }

}
