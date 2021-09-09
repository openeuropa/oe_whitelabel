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
   * Construct the footer block object.
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
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
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
      'source_logo' => 'ec',
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
    $form['source_logo'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo Source'),
      '#description' => $this->t('Please select the source for the logo.'),
      '#options' => $sourceLogoOptions,
      '#default_value' => $config['source_logo'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('source_logo', $form_state->getValue('source_logo'));
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

    if ($config['source_logo'] == 'ec') {
      $uri = base_path() . drupal_get_path('module', 'oe_whitelabel_logo') . '/images/logos/' . $config['source_logo'] . '/europa-flag.gif';
      // Empty to let the gif be printed with its width.
      $width = '';
    }
    else {
      $uri = base_path() . drupal_get_path('module', 'oe_whitelabel_logo') . '/images/logos/' . $config['source_logo'] . '/logo--' . $language . '.svg';
      // Value of width as per EU sites.
      $width = '290px';
    }
    drupal_Set_message($uri);
    $config = $this->configFactory->get('system.site');
    $title = $config->get('name');

    $image = [
      '#theme' => 'image',
      '#uri' => $uri,
      '#width' => $width,
      '#alt' => $title,
      '#title' => $title,
    ];

    $cache->addCacheableDependency($image);
    $cache->applyTo($image);

    return [
      'image' => $image,
    ];
  }

}
