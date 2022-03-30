<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\PatternAssertions;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Assertions for the card pattern.
 *
 * @see ./templates/patterns/card/card.ui_patterns.yml
 */
class CardAssert extends BasePatternAssert {

  /**
   * {@inheritdoc}
   */
  protected function getAssertions($variant): array {
    return [
      'title' => [
        [$this, 'assertElementText'],
        'div.card.listing-item .card-title a span',
      ],
      'url' => [
        [$this, 'assertElementAttribute'],
        'div.card.listing-item .card-title a',
        'href',
      ],
      'image' => [
        [$this, 'assertCardImage'],
        $variant,
      ],
      'description' => [
        [$this, 'assertElementText'],
        '.card-text',
      ],
      'badges' => [
        [$this, 'assertBadgesElements'],
      ],
      'content' => [
        [$this, 'assertContent'],
      ],
    ];
  }

  /**
   * Asserts the image of a card.
   *
   * @param array|null $expected_image
   *   The expected image values.
   * @param string $variant
   *   The variant of the pattern being checked.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler where to check the element.
   */
  protected function assertCardImage($expected_image, string $variant, Crawler $crawler): void {
    if ($variant == 'search') {
      $image_div = $crawler->filter('div.card div.col-md-3.col-lg-2.rounded.mw-listing-img img.card-img-top');
      self::assertEquals($expected_image['alt'], $image_div->attr('alt'));
      self::assertStringContainsString($expected_image['src'], $image_div->attr('src'));
    }
    else {
      $image_div = $crawler->filter('div.card img');
      self::assertEquals($expected_image['alt'], $image_div->attr('alt'));
      self::assertStringContainsString($expected_image['src'], $image_div->attr('src'));
    }
  }

  /**
   * Asserts the content of a card.
   *
   * @param array $expected_items
   *   The expected item values.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler where to check the element.
   */
  protected function assertContent(array $expected_items, Crawler $crawler): void {
    foreach ($expected_items as $expected_item) {
      self::assertStringContainsString($expected_item, $crawler->html());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function assertBaseElements(string $html, string $variant): void {
    $crawler = new Crawler($html);
    $base_selector = 'article ' . $this->getBaseItemClass($variant);
    $card = $crawler->filter($base_selector);
    self::assertCount(1, $card);
  }

  /**
   * Returns the base CSS selector for a list item depending on the variant.
   *
   * @param string $variant
   *   The variant being checked.
   *
   * @return string
   *   The base selector for the variant.
   */
  protected function getBaseItemClass(string $variant): string {
    switch ($variant) {
      case 'search':
        return 'div.listing-item.card';

      default:
        return 'div.card';
    }
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function getPatternVariant(string $html): string {
    $crawler = new Crawler($html);
    if ($crawler->filter('div.mw-listing-img')) {
      return 'search';
    }
    return 'default';
  }

}
