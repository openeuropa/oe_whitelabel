<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Exposes a block with EU/EC logo (Corporate Block).
 *
 * @Block(
 *  id = "whitelabel_logo_block",
 *  admin_label = @Translation("Corporate Logo Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class CorporateLogoBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Construct CorporateLogoBlock object.
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
  public function defaultConfiguration(): array {
    return [
      'logo_source' => 'ec',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $sourceLogoOptions = [
      'eu' => $this->t('EU logo'),
      'ec' => $this->t('EC logo'),
    ];
    $form['logo_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo Source'),
      '#description' => $this->t('Please select the source for the logo.'),
      '#options' => $sourceLogoOptions,
      '#default_value' => $config['logo_source'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('logo_source', $form_state->getValue('logo_source'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['languages:language_interface']);
    $config = $this->getConfiguration();
    $cache->addCacheableDependency($config);

    $logo_path = drupal_get_path('module', 'oe_whitelabel_helper') . '/images/logos/' . $config['logo_source'];
    $title = $this->configFactory->get('system.site')->get('name');

    if ($config['logo_source'] === 'eu') {
      $build = $this->buildEuLogo($logo_path, $language, $title);
    }
    else {
      $build = $this->buildEcLogo($logo_path, $language, $title);
    }

    $cache->applyTo($build);

    return $build;
  }

  /**
   * Build the EU logo.
   *
   * @param string $logo_path
   *   The logo path.
   * @param string $language
   *   The language code.
   * @param string $title
   *   The site title.
   *
   * @return array
   *   The render array.
   */
  protected function buildEuLogo(string $logo_path, string $language, string $title): array {
    $image = [
      '#theme' => 'image',
      '#uri' => $logo_path . '/logo-eu--' . $language . '.normal.svg',
      '#width' => '290px',
      '#alt' => $title,
      '#title' => $title,
    ];
    return [
      '#type' => 'inline_template',
      '#template' => '<picture><source media="(max-width: 25em)" srcset="{{ mobile }}">{{ image }}</picture>',
      '#context' => [
        'image' => $image,
        'mobile' => $logo_path . '/logo-eu--' . $language . '.mobile.svg',
      ],
    ];
  }

  /**
   * Build the EC logo.
   *
   * @param string $logo_path
   *   The logo path.
   * @param string $language
   *   The language code.
   * @param string $title
   *   The site title.
   *
   * @return array
   *   The render array.
   */
  protected function buildEcLogo(string $logo_path, string $language, string $title): array {
    return [
      '#theme' => 'image',
      '#uri' => $logo_path . '/logo--' . $language . '.svg',
      '#width' => '290px',
      '#alt' => $title,
      '#title' => $title,
    ];
  }

}
