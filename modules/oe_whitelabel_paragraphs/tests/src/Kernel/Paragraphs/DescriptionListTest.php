<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\filter\Entity\FilterFormat;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the "Description list" paragraphs.
 */
class DescriptionListTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 0,
      'filters' => [],
    ])->save();

    FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<em>',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests the rendering of the paragraph type.
   */
  public function testRendering(): void {
    // Create Description list paragraph with horizontal variant.
    $paragraph = Paragraph::create([
      'type' => 'oe_description_list',
      'field_oe_title' => 'Description list paragraph',
      'oe_w_orientation' => 'horizontal',
      'field_oe_description_list_items' => [
        [
          'term' => 'Aliquam ultricies',
          'description' => 'Donec et leo ac velit posuere tempor <em>mattis</em> ac mi. Vivamus nec <strong>dictum</strong> lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
          'format' => 'full_html',
        ],
        [
          'term' => 'Etiam <em>lacinia</em>',
          'description' => 'Quisque tempor sollicitudin <em>lacinia</em>. Morbi imperdiet nulla et nunc <strong>aliquet</strong>, vel lobortis nunc cursus. Mauris vitae hendrerit felis.',
          'format' => 'filtered_html',
        ],
      ],
    ]);
    $paragraph->save();

    // Testing: Description list paragraph with horizontal variant.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $title = $crawler->filter('h2.bcl-heading');
    $this->assertCount(1, $title);
    $this->assertEquals('Description list paragraph', $title->text());

    $description_lists = $crawler->filter('.bcl-description-list');
    $this->assertCount(1, $description_lists);
    $description_list_assert = new DescriptionListAssert();

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Aliquam ultricies',
          'definition' => 'Donec et leo ac velit posuere tempor mattis ac mi. Vivamus nec dictum lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
        ],
        [
          'term' => 'Etiam <em>lacinia</em>',
          'definition' => 'Quisque tempor sollicitudin lacinia. Morbi imperdiet nulla et nunc aliquet, vel lobortis nunc cursus. Mauris vitae hendrerit felis.',
        ],
      ],
    ], $description_lists->outerHtml());

    // Testing: Description list paragraph with vertical variant.
    $paragraph->get('oe_w_orientation')->setValue('vertical');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $title = $crawler->filter('h2.bcl-heading');
    $this->assertEquals('Description list paragraph', $title->text());

    $description_lists = $crawler->filter('.bcl-description-list');
    $this->assertCount(1, $description_lists);

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Aliquam ultricies',
          'definition' => 'Donec et leo ac velit posuere tempor mattis ac mi. Vivamus nec dictum lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
        ],
        [
          'term' => 'Etiam <em>lacinia</em>',
          'definition' => 'Quisque tempor sollicitudin lacinia. Morbi imperdiet nulla et nunc aliquet, vel lobortis nunc cursus. Mauris vitae hendrerit felis.',
        ],
      ],
    ], $description_lists->outerHtml());
  }

}
