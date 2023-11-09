<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Link;
use Drupal\oe_link_lists\LinkInterface;

/**
 * Teaser display for link lists.
 *
 * @LinkDisplay(
 *   id = "oewt_teaser",
 *   label = @Translation("Teaser"),
 *   description = @Translation("Display list link links using Teaser view display."),
 *   bundles = { "dynamic" }
 * )
 */
class TeaserDisplayPlugin extends EntityViewDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplayModeId(): string {
    return 'teaser';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLinkWithFallback(LinkInterface $link): array {
    return [
      '#type' => 'pattern',
      '#id' => 'card',
      '#variant' => 'search',
      '#fields' => [
        'title' => Link::fromTextAndUrl($link->getTitle(), $link->getUrl())->toRenderable(),
        'text' => $link->getTeaser(),
      ],
    ];
  }

}
