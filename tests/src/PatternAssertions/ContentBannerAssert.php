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
        '.card-body',
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

  /**
   * Asserts the badges items of the pattern.
   *
   * @param array $badges
   *   The expected badges item values.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler where to check the element.
   */
  protected function assertBadgesElements(array $badges, Crawler $crawler): void {
    if (empty($badges)) {
      $this->assertElementNotExists('.badge', $crawler);
      return;
    }
    $badges_items = $crawler->filter('.mt-2-5');
    self::assertCount(count($badges), $badges_items);
    foreach ($badges as $index => $badge) {
      self::assertEquals($badge, trim($badges_items->eq($index)->text()));
    }
  }

}
