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
  protected function getAssertions(string $variant): array {
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
        [$this, 'assertDescription'],
      ],
      'meta' => [
        [$this, 'assertMeta'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertBaseElements(string $html, string $variant): void {
    $crawler = new Crawler($html);
    $page_header = $crawler->filter('.bcl-content-banner');
    self::assertCount(1, $page_header);
  }

  /**
   * Asserts the content banner description.
   *
   * @param string|null $text
   *   The expected description. Null if no description should be present.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The dom crawler.
   */
  protected function assertDescription(?string $text, Crawler $crawler): void {
    // The description wrapper is always rendered, even if empty.
    $element = $crawler->filter('.card-body > div:last-child');
    self::assertCount(1, $element);

    self::assertEquals($text ?: '', trim($element->text()));
  }

  /**
   * Asserts the content banner meta.
   *
   * @param array $expected
   *   The expected meta.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The dom crawler.
   */
  protected function assertMeta(array $expected, Crawler $crawler): void {
    $actual = $crawler->filter('.card-body span.text-muted.me-3')->each(function (Crawler $element) {
      return trim($element->text());
    });

    $this->assertEquals($expected, $actual);
  }

}
