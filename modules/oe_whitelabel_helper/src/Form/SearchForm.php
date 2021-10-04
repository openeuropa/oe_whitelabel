<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Form;

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
    $input_type = !empty($config['view_options']['enable_autocomplete']) ? 'search_api_autocomplete' : 'textfield';
    $form['#action'] = $config['form']['action'];
    $form['search_input'] = [
      '#type' => $input_type,
      // The view id.
      '#search_id' => $config['view_options']['id'],
      '#additional_data' => [
        'display' => $config['view_options']['display'],
        'filter' => $config['input']['name'],
      ],
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'],
      ],
    ];
    $form['submit'] = [
      '#prefix' => '<div class="ms-2">',
      '#suffix' => '</div>',
      '#type' => !empty($config['button']['type']) ? $config['button']['type'] : 'submit',
      '#name' => FALSE,
      '#value' => $config['button']['label'],
    ];
    $form['submit']['#attributes']['class'][] = 'btn-md';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $language = $this->languageManager->getCurrentLanguage();
    $config = $form_state->get('oe_whitelabel_search_config');
    $url = Url::fromUri('internal:' . $config['form']['action'], [
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
