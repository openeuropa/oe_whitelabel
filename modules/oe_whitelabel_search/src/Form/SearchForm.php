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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $config = NULL): array {
    if (empty($config['input']['name'])) {
      return [];
    }

    $form_state->set('oe_whitelabel_search_config', $config);

    $theme_hook_suffix = $this->getFormId();
    if (isset($config['form']['region'])) {
      $theme_hook_suffix = $config['form']['region'] . '__' . $theme_hook_suffix;
    }

    $form['#theme_wrappers'] = ['form__' . $theme_hook_suffix];

    $form['search_input'] = [
      /* @see \Drupal\Core\Render\Element\Textfield */
      '#type' => 'textfield',
      '#theme' => 'input__textfield__' . $theme_hook_suffix,
      '#theme_wrappers' => ['form_element__' . $this->getFormId()],
      '#title' => $config['input']['label'],
      '#title_display' => 'invisible',
      '#size' => 20,
      '#default_value' => $this->getRequest()->get($config['input']['name']),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'],
      ],
    ];

    $form['submit'] = [
      /* @see \Drupal\Core\Render\Element\Submit */
      '#type' => 'submit',
      '#theme_wrappers' => ['input__submit__' . $theme_hook_suffix],
      '#value' => $config['button']['label'],
    ];

    if (!$config['view_options']['enable_autocomplete']) {
      return $form;
    }

    /* @see \Drupal\search_api_autocomplete\Element\SearchApiAutocomplete */
    $form['search_input']['#type'] = 'search_api_autocomplete';
    // The view id.
    $form['search_input']['#search_id'] = $config['view_options']['id'];
    $form['search_input']['#additional_data'] = [
      'display' => $config['view_options']['display'],
      'filter' => $config['input']['name'],
    ];

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
        $config['input']['name'] => $form_state->getValue('search_input'),
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
