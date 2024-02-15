<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_corporate_blocks\Plugin\Block\FooterBlockBase;

/**
 * Provides the Neutral footer block.
 *
 * @Block(
 *   id = "oe_corporate_blocks_neutral_footer",
 *   admin_label = @Translation("Neutral Footer block"),
 *   category = @Translation("Corporate blocks"),
 * )
 */
class NeutralFooterBlock extends FooterBlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['languages:language_interface']);

    $config = $this->configFactory->get('oe_corporate_blocks.ec_data.footer');
    $cache->addCacheableDependency($config);

    $build['#theme'] = 'oe_corporate_blocks_neutral_footer';

    NestedArray::setValue($build, ['#corporate_footer', 'content_owner_details'], $config->get('content_owner_details'));

    $this->setSiteSpecificFooter($build, $cache);

    $cache->applyTo($build);

    return $build;
  }

}
