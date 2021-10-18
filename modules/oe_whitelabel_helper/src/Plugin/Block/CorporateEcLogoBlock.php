<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Exposes a block with EC logo (Corporate Block).
 *
 * @Block(
 *  id = "whitelabel_ec_logo_block",
 *  admin_label = @Translation("Corporate EC Logo Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class CorporateEcLogoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $languageManager;

  /**
   * Construct CorporateEcLogoBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['languages:language_interface']);

    $logo_path = drupal_get_path('module', 'oe_whitelabel_helper') . '/images/logos/ec';
    $title = $this->configFactory->get('system.site')->get('name');

    $image = [
      '#theme' => 'image',
      '#uri' => $logo_path . '/logo-ec--' . $language . '.svg',
      '#alt' => $title,
      '#title' => $title,
    ];

    $build = [
      '#type' => 'link',
      '#url' => Url::fromUri('https://ec.europa.eu/info/index_' . $language, [
        'attributes' => [
          'class' => [
            'navbar-brand',
          ],
          'target' => '_blank',
        ],
      ]),
      '#title' => $image,
    ];

    $cache->applyTo($build);

    return $build;
  }

}
