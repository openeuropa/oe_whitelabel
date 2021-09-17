<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Exposes a block with EC logo (Corporate Block).
 *
 * @Block(
 *  id = "whitelabel_search_block",
 *  admin_label = @Translation("Whitelabel Search Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class WhitelabelSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['form_action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form action'),
      '#description' => $this->t('The url the form should submit to.'),
      '#default_value' => $config['form']['action'],
      '#required' => TRUE,
    ];
    $form['form_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form extra classes'),
      '#description' => $this->t('Add string of css classes separated by space.'),
      '#default_value' => $config['form']['classes'],
    ];
    $form['input_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input name'),
      '#description' => $this->t('A name for the search input.'),
      '#default_value' => $config['input']['name'],
      '#required' => TRUE,
    ];
    $form['input_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input Label'),
      '#description' => $this->t('A label text for the search input.'),
      '#default_value' => $config['input']['label'],
    ];
    $form['input_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input extra classes'),
      '#description' => $this->t('Add string of css classes separated by space.'),
      '#default_value' => $config['input']['classes'],
    ];
    $form['input_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input placeholder text'),
      '#default_value' => $config['input']['placeholder'],
    ];
    $form['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#description' => $this->t('Label text that should appear inside the button.'),
      '#default_value' => $config['button']['label'],
    ];
    $form['button_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button type'),
      '#description' => $this->t('Ex: submit, button.'),
      '#default_value' => $config['button']['type'],
    ];
    $form['button_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button extra classes'),
      '#description' => $this->t('Add string of css classes separated by space.'),
      '#default_value' => $config['button']['classes'],
    ];
    $form['button_icon_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button icon name'),
      '#description' => $this->t('Ex: search.'),
      '#default_value' => $config['button']['icon']['name'],
    ];
    $form['button_icon_position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button icon position'),
      '#description' => $this->t('The position of the icon inside the button.'),
      '#default_value' => $config['button']['icon']['position'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('form', [
      'action' => $form_state->getValue('form_action'),
      'classes' => $form_state->getValue('form_classes'),
    ]);
    $this->setConfigurationValue('input', [
      'name' => $form_state->getValue('input_name'),
      'label' => $form_state->getValue('input_label'),
      'classes' => $form_state->getValue('input_classes'),
      'placeholder' => $form_state->getValue('input_placeholder'),
    ]);
    $this->setConfigurationValue('button', [
      'label' => $form_state->getValue('button_label'),
      'type' => $form_state->getValue('button_type'),
      'classes' => $form_state->getValue('button_classes'),
      'icon' => [
        'name' => $form_state->getValue('button_icon_name'),
        'position' => $form_state->getValue('button_icon_position'),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['languages:language_interface']);
    $config = $this->getConfiguration();
    $action = Url::fromUri($config['form']['action'], [
      'language' => $language,
      'absolute' => TRUE,
    ])->toString();
    $build['search_block'] = [
      '#type' => 'pattern',
      '#id' => 'search_block',
      '#fields' => [
        'form_action' => $action,
        'form_classes' => $config['form']['classes'],
        'label' => $config['input']['label'],
        'input_classes' => $config['input']['classes'],
        'input_name' => $config['input']['name'],
        'placeholder' => $config['input']['placeholder'],
        'button_label' => $config['button']['label'],
        'button_classes' => $config['button']['classes'],
        'button_type' => $config['button']['type'],
        'button_icon' => $config['button']['icon']['name'],
        'icon_position' => $config['button']['icon']['position'],
      ],
    ];

    $cache->applyTo($build);

    return $build;
  }

}
