<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Teaser display of link lists.
 *
 * @LinkDisplay(
 *   id = "teaser",
 *   label = @Translation("Teaser"),
 *   description = @Translation("Display a Link lists using Teaser view display."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class TeaserDisplayPlugin extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

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
