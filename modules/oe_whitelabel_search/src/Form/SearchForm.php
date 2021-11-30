<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Routing\TrustedRedirectResponse;

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
    $form_state->set('oe_whitelabel_search_config', $config);
    $input_value = '';

    $form['#attributes']['class'][] = 'bcl_search_form';

    if (!empty($config['input']['name'])) {
      $input_value = $this->getRequest()->get($config['input']['name']);
    }

    $form['#action'] = $config['form']['action'];

    $form['search_input'] = [
      '#prefix' => '<div class="bcl-search-form__group">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => $config['input']['label'],
      '#title_display' => 'invisible',
      '#size' => 20,
      '#attributes' => [
        'placeholder' => $config['input']['placeholder'],
        'class' => [
          $config['input']['classes'],
          'border-start-0',
          'rounded-0',
          'rounded-start',
        ],
      ],
      '#default_value' => $input_value,
      '#required' => TRUE,
    ];

    // $button = [
    //   '#type' => 'submit',
    //   '#id' => 'button',
    //   '#variant' => 'light',
    //     '#attributes' => [
    //       'class' => [
    //         'border-start-0',
    //         'rounded-0 rounded-end',
    //         'd-flex',
    //         'btn btn-light',
    //         'btn-md',
    //         'py-2',
    //         $config['button']['classes'],
    //       ],
    //     ],
    //   '#fields' => [
    //     'settings' => [
    //       'type' => 'submit',
    //     ],
    //     'attributes' => [
    //       'class' => [
    //         'border-start-0',
    //         'rounded-0 rounded-end',
    //         'd-flex',
    //         'btn btn-light',
    //         'btn-md',
    //         'py-2',
    //         $config['button']['classes'],
    //       ],
    //     ],
    //   ],
    // ];
    // if ($config['button']['label_icon'] != 'icon') {
    //   $button['#fields']['label'] = $this->t($config['button']['label']);
    //   $button['#value'] = $this->t($config['button']['label']);
    // }

    $button = [
      '#type' => 'pattern',
      '#id' => 'button',
      '#variant' => 'light',
      '#fields' => [
        'settings' => [
          'type' => 'submit',
        ],
        'attributes' => [
          'id' => 'submit',
          'class' => [
            'border-start-0',
            'rounded-0 rounded-end',
            'd-flex',
            'btn btn-light',
            'btn-md',
            'py-2',
            $config['button']['classes'],
          ],
        ],
      ],
    ];
    if ($config['button']['label_icon'] != 'icon') {
      $button['#fields']['label'] = $this->t($config['button']['label']);
    }
    if ($config['button']['label_icon'] != 'label') {
      $button['#fields']['icon'] = [
        'name' => 'search',
        'size' => 'xs',
      ];
    }

    $form['submit'] = $button;

    if (!$config['view_options']['enable_autocomplete']) {
      return $form;
    }

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
