<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Link;
use Drupal\oe_link_lists\LinkInterface;

/**
 * Card display for link lists.
 *
 * @LinkDisplay(
 *   id = "card",
 *   label = @Translation("Card"),
 *   description = @Translation("Display link list links using Card view display."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class CardDisplayPlugin extends ColumnLinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function buildLinkWithFallback(LinkInterface $link): array {
    return [
      '#type' => 'pattern',
      '#id' => 'card',
      '#fields' => [
        'title' => Link::fromTextAndUrl($link->getTitle(), $link->getUrl())->toRenderable(),
        'text' => $link->getTeaser(),
      ],
    ];
  }

}
