<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

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

    $this->assertCount(1, $crawler->filter('h2.bcl-heading'));
    $this->assertCount(1, $crawler->filter('dl.d-md-grid.grid-3-9'));
    $this->assertCount(2, $crawler->filter('dd'));
    $this->assertCount(2, $crawler->filter('dt'));

    $title = $crawler->filter('h2.bcl-heading');
    $this->assertEquals('Description list paragraph', $title->text());

    $term_1 = $crawler->filter('dl > div:nth-child(1) > dt');
    $this->assertEquals('Aliquam ultricies', $term_1->html());
    $description_1 = $crawler->filter('dl > div:nth-child(1) + dd > div');
    $this->assertEquals(
      'Donec et leo ac velit posuere tempor <em>mattis</em> ac mi. Vivamus nec <strong>dictum</strong> lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
      $description_1->html()
    );

    $term_2 = $crawler->filter('dl > div:nth-child(3) > dt');
    $this->assertEquals('Etiam &lt;em&gt;lacinia&lt;/em&gt;', $term_2->html());
    $description_2 = $crawler->filter('dl > div:nth-child(3) + dd > div');
    $this->assertEquals(
      'Quisque tempor sollicitudin <em>lacinia</em>. Morbi imperdiet nulla et nunc aliquet, vel lobortis nunc cursus. Mauris vitae hendrerit felis.',
      $description_2->html()
    );

    // Testing: Description list paragraph with vertical variant.
    $paragraph->get('oe_w_orientation')->setValue('vertical');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $title = $crawler->filter('h2.bcl-heading');
    $this->assertEquals('Description list paragraph', $title->text());

    $term_1 = $crawler->filter('dl > dt:nth-child(1)');
    $this->assertEquals('Aliquam ultricies', $term_1->html());
    $description_1 = $crawler->filter('dl > dt:nth-child(1) + dd');
    $this->assertEquals(
      'Donec et leo ac velit posuere tempor <em>mattis</em> ac mi. Vivamus nec <strong>dictum</strong> lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
      $description_1->html()
    );

    $term_2 = $crawler->filter('dl > dt:nth-child(3)');
    $this->assertEquals('Etiam &lt;em&gt;lacinia&lt;/em&gt;', $term_2->html());
    $description_2 = $crawler->filter('dl > dt:nth-child(3) + dd');
    $this->assertEquals(
      'Quisque tempor sollicitudin <em>lacinia</em>. Morbi imperdiet nulla et nunc aliquet, vel lobortis nunc cursus. Mauris vitae hendrerit felis.',
      $description_2->html()
    );
  }

}
