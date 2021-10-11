<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes the facets as a form.
 */
class SearchForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $languageManager;

  /**
   * Constructs an instance of ListFacetsForm.
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
    $parameter = \Drupal::request()->get($config['input']['name']);
    $form['search_input'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'] ?? $config['input_placeholder'],
      ],
      '#default_value' => $parameter ?? '',
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

    $form['submit'] = [
      '#prefix' => '<div class="ms-2">',
      '#suffix' => '</div>',
      '#type' => $config['button']['type'] ?? $config['button_type'],
      '#name' => FALSE,
      '#value' => $config['button']['label'] ?? $config['button_label'],
    ];
    $form['submit']['#attributes']['class'][] = 'btn-md';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $language = $this->languageManager->getCurrentLanguage();
    $config = $form_state->get('oe_whitelabel_search_config');
    $url =
      Url::fromUri('base:' . $config['form']['action'], [
        'language' => $language,
        'absolute' => TRUE,
        'query' => [
          $config['input']['name'] => $form_state->getValue($config['input']['name']),
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
