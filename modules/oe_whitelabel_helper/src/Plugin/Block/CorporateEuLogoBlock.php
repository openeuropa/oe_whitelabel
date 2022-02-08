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
 * Exposes a block with EU logo (Corporate Block).
 *
 * @Block(
 *  id = "whitelabel_eu_logo_block",
 *  admin_label = @Translation("Corporate EU Logo Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class CorporateEuLogoBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $logo_path = drupal_get_path('module', 'oe_whitelabel_helper') . '/images/logos/eu';
    $title = $this->configFactory->get('system.site')->get('name');

    $image = [
      '#theme' => 'image',
      '#uri' => $logo_path . '/logo-eu--' . $language . '.svg',
      '#alt' => $title,
      '#title' => $title,
      '#attributes' => [
        'class' => ['d-none', 'd-lg-block'],
      ],
    ];

    $mobile_url = file_create_url($logo_path . '/mobile/logo-eu--' . $language . '.svg');

    $inline_template = [
      '#type' => 'inline_template',
      '#template' => '<picture><source media="(max-width: 992px)" srcset="{{ mobile }}">{{ image }}</picture>',
      '#context' => [
        'image' => $image,
        'mobile' => file_url_transform_relative($mobile_url),
      ],
    ];

    $build = [
      '#type' => 'link',
      '#url' => Url::fromUri('https://europa.eu/european-union/index_' . $language, [
        'attributes' => [
          'class' => [
            'navbar-brand',
          ],
          'target' => '_blank',
        ],
      ]),
      '#title' => $inline_template,
    ];

    $cache->applyTo($build);

    return $build;
  }

}
