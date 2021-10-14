<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configurable search form class.
 */
class SearchForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $languageManager;

  /**
   * Constructs an instance of SearchForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $config = NULL): array {
    $form_state->set('oe_whitelabel_search_config', $config);
    $enable_autocomplete = $config['view_options']['enable_autocomplete'] ?? $config['enable_autocomplete'];
    $form['#action'] = $config['form']['action'] ?? $config['form_action'];
    isset($config['input']['name']) ? $parameter = \Drupal::request()->get($config['input']['name']) : $parameter = '';
    isset($config['input']['classes']) ? $classes = $config['input']['classes'] : $classes = '';
    isset($config['button']['classes']) ? $classesButton = $config['button']['classes'] : $classesButton = '';
    if (empty($classes) && isset($config['input_classes'])) {
      $classes = $config['input_classes'];
    }
    $form['search_input'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'] ?? $config['input_placeholder'],
        'class' => (array) $classes,
      ],
      '#default_value' => $parameter,
      '#required' => TRUE,
    ];
    if ($enable_autocomplete) {
      $form['search_input']['#type'] = 'search_api_autocomplete';
      // The view id.
      $form['search_input']['#search_id'] = $config['view_options']['id'] ?? $config['view_id'];
      $form['search_input']['#additional_data'] = [
        'display' => $config['view_options']['display'] ?? $config['view_display'],
        'filter' => $config['input']['name'] ?? $config['input_name'],
      ];
    }
    if (empty($classesButton) && isset($config['button_classes'])) {
      $classesButton = $config['button_classes'];
    }
    $form['submit'] = [
      '#prefix' => '<div class="ms-2">',
      '#suffix' => '</div>',
      '#type' => $config['button']['type'] ?? $config['button_type'],
      '#name' => FALSE,
      '#value' => $config['button']['label'] ?? $config['button_label'],
      '#attributes' => [
        'class' => (array) $classesButton,
      ],
    ];
    $form['submit']['#attributes']['class'][] = 'btn-md';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $form_state->get('oe_whitelabel_search_config');
    $url = Url::fromUri('base:' . $config['form']['action'], [
      'language' => $this->languageManager->getCurrentLanguage(),
      'absolute' => TRUE,
      'query' => [
        $config['input']['name'] => $form_state->getValue("search_input"),
      ],
    ]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'oe_whitelabel_search_form';
  }

}
