<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\PatternAssertions;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Assertions for the page header pattern.
 */
class ContentBannerAssert extends BasePatternAssert {

  /**
   * {@inheritdoc}
   */
  protected function getAssertions(): array {
    return [
      'image' => [
        [$this, 'assertImage'],
        '.card-img-top',
      ],
      'badges' => [
        [$this, 'assertBadgesElements'],
      ],
      'title' => [
        [$this, 'assertElementText'],
        '.card-title',
      ],
      'description' => [
        [$this, 'assertElementText'],
        '.card-body .mt-4',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertBaseElements(string $html): void {
    $crawler = new Crawler($html);
    $page_header = $crawler->filter('.bcl-content-banner');
    self::assertCount(1, $page_header);
  }

}
