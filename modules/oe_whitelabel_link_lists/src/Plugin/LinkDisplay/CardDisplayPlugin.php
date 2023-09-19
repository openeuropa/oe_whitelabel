<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Card display of link lists.
 *
 * @LinkDisplay(
 *   id = "card",
 *   label = @Translation("Card"),
 *   description = @Translation("Display a Link lists using Card view display."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class CardDisplayPlugin extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

}
