<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Prepares the social media links of the corporate footers.
 */
class FooterSocialMediaPreprocess implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Adds the social media links display settings to the footer variables.
   *
   * @param array $variables
   *   The footer template variables.
   */
  public function preprocess(array &$variables): void {
    $config = $this->configFactory->get('oe_corporate_blocks.social_media_footer_links_settings');
    CacheableMetadata::createFromRenderArray($variables)
      ->addCacheableDependency($config)
      ->applyTo($variables);

    $variables['social_media_follow'] = [
      'alignment' => $config->get('alignment') === 'horizontal' ? 'horizontal' : 'vertical',
      'hide_labels' => !$config->get('display_labels'),
    ];
  }

}
