<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CardPatternAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\FilePatternAssert;
use Drupal\Tests\oe_whitelabel\PatternAssertions\ContentBannerAssert;
use Drupal\Tests\oe_whitelabel\PatternAssertions\InPageNavigationAssert;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;

/**
 * Tests the publication content type.
 */
class PublicationContentRenderTest extends WhitelabelBrowserTestBase {

  use MediaCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_starter_publication',
  ];

  /**
   * Tests the canonical page rendering.
   */
  public function testCanonicalPage(): void {
    $document = $this->createDocumentMedia();

    // Create a publication node with the minimal required fields.
    $node = $this->drupalCreateNode([
      'type' => 'oe_sc_publication',
      'title' => 'Test publication',
      'body' => [],
      'oe_sc_publication_document' => $document->id(),
      'oe_publication_date' => '2022-08-01',
    ]);

    $this->drupalGet($node->toUrl());

    $assert_session = $this->assertSession();
    $content_banner_assert = new ContentBannerAssert();
    $content_banner_assert->assertPattern([
      'title' => 'Test publication',
      'meta' => [
        '01 August 2022',
      ],
      'badges' => [],
      'image' => NULL,
    ], $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    $inpage_nav_assert = new InPageNavigationAssert();
    $inpage_nav_assert->assertPattern([
      'title' => 'Page content',
      'links' => [
        [
          'label' => 'Document',
          'href' => '#document',
        ],
      ],
    ], $assert_session->elementExists('css', 'nav.bcl-inpage-navigation')->getOuterHtml());

    // Assert that the file is rendered together with the field display label.
    $this->assertEquals('Document', $assert_session->elementExists('css', 'h2#document')->getText());
    $expected_document = [
      'file' => [
        'title' => 'Document title',
        'language' => 'English',
        'url' => $document->get('oe_media_file')->entity->createFileUrl(FALSE),
        'meta' => '(2.96 KB - PDF)',
        'icon' => 'file-pdf-fill',
      ],
      'translations' => NULL,
      'link_label' => 'Download',
    ];
    $assert = new FilePatternAssert();
    $assert->assertPattern($expected_document, $assert_session->elementExists('css', 'h2#document + div.mb-4-5')->getHtml());

    // Create a publication with all the fields filled in.
    $thumbnail = $this->createImageMedia();
    $description = $this->getRandomGenerator()->sentences(20);
    $short_description = $this->getRandomGenerator()->sentences(5);
    $reference_code = $this->randomString();

    $node = $this->drupalCreateNode([
      'type' => 'oe_sc_publication',
      'title' => 'Test publication 2',
      'body' => $description,
      'oe_summary' => $short_description,
      'oe_featured_media' => $thumbnail->id(),
      'oe_reference_code' => $reference_code,
      'oe_sc_publication_document' => $document->id(),
      'oe_publication_date' => '2022-08-02',
    ]);
    $this->drupalGet($node->toUrl());

    $content_banner_assert->assertPattern([
      'title' => 'Test publication 2',
      'meta' => [
        '02 August 2022',
      ],
      'description' => $short_description,
      'badges' => [],
      'image' => [
        'alt' => 'Alt text',
        'src' => 'example_1.jpeg',
      ],
    ], $assert_session->elementExists('css', '.bcl-content-banner')->getOuterHtml());

    $inpage_nav_assert->assertPattern([
      'title' => 'Page content',
      'links' => [
        [
          'label' => 'Reference code',
          'href' => '#reference-code',
        ],
        [
          'label' => 'Description',
          'href' => '#description',
        ],
        [
          'label' => 'Document',
          'href' => '#document',
        ],
      ],
    ], $assert_session->elementExists('css', 'nav.bcl-inpage-navigation')->getOuterHtml());

    $this->assertEquals('Reference code', $assert_session->elementExists('css', 'h2#reference-code')->getText());
    $assert_session->elementTextEquals('css', 'h2#reference-code + div.mb-4-5', $reference_code);
    $this->assertEquals('Description', $assert_session->elementExists('css', 'h2#description')->getText());
    $assert_session->elementTextEquals('css', 'h2#description + div.mb-4-5', $description);
    $assert->assertPattern($expected_document, $assert_session->elementExists('css', 'h2#document + div.mb-4-5')->getHtml());
  }

  /**
   * Tests the teaser view mode rendering.
   */
  public function testTeaserViewMode(): void {
    // Create a publication with all the fields filled in.
    $thumbnail = $this->createImageMedia();
    $title = $this->randomString();
    $short_description = $this->getRandomGenerator()->sentences(5);

    $node = $this->drupalCreateNode([
      'type' => 'oe_sc_publication',
      'title' => $title,
      'oe_summary' => $short_description,
      'oe_featured_media' => $thumbnail->id(),
      'oe_publication_date' => '2022-08-02',
    ]);

    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $card_assert = new CardPatternAssert();
    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern([
      'title' => $title,
      'url' => $node->toUrl()->toString(),
      'description' => $short_description,
      'content' => [
        '02 August 2022',
      ],
      'image' => [
        'alt' => 'Alt text',
        'src' => 'example_1.jpeg',
      ],
      'badges' => [],
    ], $html);
  }

}
