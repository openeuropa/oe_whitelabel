<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_whitelabel_search\Form\SearchForm;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes a block with search box.
 *
 * @Block(
 *  id = "whitelabel_search_block",
 *  admin_label = @Translation("Whitelabel Search Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class SearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct SearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
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
      $container->get('form_builder'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Most of these placeholder values must be overwritten in the block
    // creation form.
    return [
      'form' => [
        'action' => '',
        'region' => '',
      ],
      'input' => [
        'name' => '',
        'label' => '',
        'placeholder' => $this->t('Search'),
      ],
      'button' => [
        'label' => '',
      ],
      'view_options' => [
        'enable_autocomplete' => FALSE,
        'id' => '',
        'display' => '',
      ],
    ];
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
      '#description' => $this->t('The url the form should submit to. Is the url of the Search API view set at the view page settings.'),
      '#default_value' => $config['form']['action'],
      '#required' => TRUE,
    ];
    $form['input'] = [
      '#type' => 'details',
      '#title' => $this->t('Input Field Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Fill in the settings of the Input field.'),
    ];
    $form['input']['input_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input name'),
      '#description' => $this->t('A name for the search input. Is the Query parameter of the contextual filter used at the Search API view.'),
      '#default_value' => $config['input']['name'],
      '#required' => TRUE,
    ];
    $form['input']['input_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input Label'),
      '#description' => $this->t('A label text for the search input.'),
      '#default_value' => $config['input']['label'],
    ];
    $form['input']['input_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input placeholder text'),
      '#description' => $this->t('The placeholder that will be shown inside the input field.'),
      '#default_value' => $config['input']['placeholder'],
    ];
    $form['button'] = [
      '#type' => 'details',
      '#title' => $this->t('Button Field Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Fill in the settings of the Button field.'),
    ];
    $form['button']['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#description' => $this->t('A label text for the button input.'),
      '#default_value' => $config['button']['label'],
    ];
    $form['enable_autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autocomplete'),
      '#default_value' => $config['view_options']['enable_autocomplete'],
    ];

    if (!$this->moduleHandler->moduleExists('views') || !$this->moduleHandler->moduleExists('search_api_autocomplete')) {
      $form['enable_autocomplete']['#disabled'] = TRUE;
      $form['enable_autocomplete']['#description'] = $this->t('Available with the views and search_api_autocomplete modules.');

      return $form;
    }

    $form['view_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View id'),
      '#description' => $this->t('The view id will be the machine name for the view.'),
      '#default_value' => $config['view_options']['id'],
      '#states' => [
        'visible' => [
          ':input[name="settings[enable_autocomplete]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[enable_autocomplete]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['view_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display'),
      '#description' => $this->t('The view display will be the machine name of the views display.'),
      '#default_value' => $config['view_options']['display'],
      '#states' => [
        'visible' => [
          ':input[name="settings[enable_autocomplete]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[enable_autocomplete]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->setConfigurationValue('form', [
      'action' => $form_state->getValue('form_action'),
      'region' => $form_state->getCompleteFormState()->getValue('region'),
    ]);
    $input = $values['input'];
    $this->setConfigurationValue('input', [
      'name' => $input['input_name'],
      'label' => $input['input_label'],
      'placeholder' => $input['input_placeholder'],
    ]);
    $button = $values['button'];
    $this->setConfigurationValue('button', [
      'label' => $button['button_label'],
    ]);
    $this->setConfigurationValue('view_options', [
      'id' => $form_state->getValue('view_id'),
      'display' => $form_state->getValue('view_display'),
      'enable_autocomplete' => $form_state->getValue('enable_autocomplete'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if (!$this->moduleHandler->moduleExists('views') || !$this->moduleHandler->moduleExists('search_api_autocomplete')) {
      return;
    }

    if (empty($values['enable_autocomplete'])) {
      return;
    }

    $view = View::load($values['view_id']);

    if (!$view) {
      $form_state->setErrorByName('view_id', $this->t('View id was not found.'));
      return;
    }

    if (!$view->getDisplay($values['view_display'])) {
      $form_state->setErrorByName('view_display', $this->t('View display was not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $build = $this->formBuilder->getForm(SearchForm::class, $config);
    $cache = CacheableMetadata::createFromRenderArray($build);
    $cache->addCacheableDependency($config);
    $cache->addCacheContexts(['url.query_args']);
    $cache->applyTo($build);

    return $build;
  }

}
