<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Tests\oe_whitelabel_paragraphs\Kernel\PatternAssertions\LinksBlockAssertion;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the "Social media follow" paragraphs.
 */
class LinksBlockTest extends LinksBlockAssertion {

  /**
   * Tests the rendering of the paragraph type.
   */
  public function testRendering(): void {
    // Create Links Block paragraph.
    $paragraph = Paragraph::create([
      'type' => 'oe_links_block',
      'field_oe_text' => 'More information',
      'oe_w_links_block_orientation' => 'vertical',
      'oe_w_links_block_background' => 'gray',
      'field_oe_links' => $this->getBlockLinks(),
    ]);
    $paragraph->save();

    // Testing: LinksBlock vertical gray.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertBackgroundGray($crawler);
    $this->assertLinksBlockRendering($crawler);
    $this->assertVerticalLinks($crawler);

    // Testing: LinksBlock horizontal gray.
    $paragraph->get('oe_w_links_block_orientation')->setValue('horizontal');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertBackgroundGray($crawler);
    $this->assertLinksBlockRendering($crawler);
    $this->assertHorizontalLinks($crawler, FALSE);

    // Testing: LinksBlock vertical transparent.
    $paragraph->get('oe_w_links_block_orientation')->setValue('vertical');
    $paragraph->get('oe_w_links_block_background')->setValue('transparent');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertBackgroundTransparent($crawler);
    $this->assertLinksBlockRendering($crawler);
    $this->assertVerticalLinks($crawler);

    // Testing: LinksBlock horizontal transparent.
    $paragraph->get('oe_w_links_block_orientation')->setValue('horizontal');
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertBackgroundTransparent($crawler);
    $this->assertLinksBlockRendering($crawler);
    $this->assertHorizontalLinks($crawler, FALSE);
  }

}
