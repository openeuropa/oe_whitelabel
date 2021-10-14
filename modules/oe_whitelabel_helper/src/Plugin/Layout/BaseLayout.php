<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides abstract class for oe_whitelabel layouts.
 */
abstract class BaseLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    return $configuration + [
      'section' => [
        'classes' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Section settings.
    $form['section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section'),
      '#tree' => TRUE,
    ];
    $form['section']['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes'),
      '#default_value' => $this->configuration['section']['classes'],
      '#description' => $this->t('Add custom classes separated by space to the section.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['section'] = $form_state->getValue('section');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $section = $this->configuration['section'];

    $build['#attributes']['class'] = [
      'layout',
      $this->getPluginDefinition()->getTemplate(),
      $section['classes'] ?: '',
    ];

    return $build;
  }

}
