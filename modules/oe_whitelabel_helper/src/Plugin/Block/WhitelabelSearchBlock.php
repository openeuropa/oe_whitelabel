<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_whitelabel_helper\Form\SearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
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
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    // @todo: add block schema in config/schema/oe_whitelabel_helper.schema
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['form_action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form action'),
      '#description' => $this->t('The url the form should submit to.'),
      '#default_value' => $config['form']['action'],
      '#required' => TRUE,
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
    // @TODO: Load the view and display dynamically, update schema.
    $form['view_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View id'),
      '#description' => $this->t('The view id (referenced as #search_id in the form).'),
      '#default_value' => !empty($config['view_options']['id']) ? $config['view_options']['id'] : 'showcase_search',
    ];
    $form['view_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display'),
      '#description' => $this->t('The view display.'),
      '#default_value' => !empty($config['view_options']['display']) ? $config['view_options']['display'] : 'showcase_search_page',
    ];
    $form['enable_autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autocomplete'),
      '#default_value' => !empty($config['view_options']['enable_autocomplete']) ? $config['view_options']['enable_autocomplete'] : 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('form', [
      'action' => $form_state->getValue('form_action'),
      // 'classes' => $form_state->getValue('form_classes'),
    ]);
    $this->setConfigurationValue('input', [
      'name' => $form_state->getValue('input_name'),
      'label' => $form_state->getValue('input_label'),
      // 'classes' => $form_state->getValue('input_classes'),
      'placeholder' => $form_state->getValue('input_placeholder'),
    ]);
    $this->setConfigurationValue('button', [
      'label' => $form_state->getValue('button_label'),
      'type' => $form_state->getValue('button_type'),
      // 'classes' => $form_state->getValue('button_classes'),
      'icon' => [
        'name' => $form_state->getValue('button_icon_name'),
        'position' => $form_state->getValue('button_icon_position'),
      ],
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
  public function build(): array {
    return $this->formBuilder->getForm(SearchForm::class, $this->getConfiguration());
  }

}
