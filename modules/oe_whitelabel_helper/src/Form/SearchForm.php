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
    // @TODO: style the form and form elements, using classes to appear like the one in the bcl.
    $form_state->set('oe_whitelabel_search_config', $config);
    // @todo: add bcl form classses: $form['#attributes']['class'][] = '...'
    $form[$config['input']['name']] = [
      '#type' => 'search_api_autocomplete',
      // The view id.
      '#search_id' => 'showcase_search',
      '#additional_data' => [
        'display' => 'showcase_search_page',
        'filter' => $config['input']['name'],
      ],
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => FALSE,
      '#value' => $config['button']['label'],
    ];

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
    // FYI: should redirect to something like search?text=tralaala,
    // where `search`is the form.action and `text` is the input.name.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'oe_whitelabel_search_form';
  }

}
