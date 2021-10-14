<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Layout;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a layout class for dynamic row layout.
 */
class DynamicRowLayout extends BaseLayout implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    return $configuration + [
      'regions' => [
        'first' => [
          'show' => 1,
          'width' => 0,
        ],
        'second' => [
          'show' => 0,
          'width' => 0,
        ],
        'third' => [
          'show' => 0,
          'width' => 0,
        ],
        'fourth' => [
          'show' => 0,
          'width' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Regions settings.
    $regions = $this->getPluginDefinition()->getRegions();
    foreach ($regions as $region_name => $region) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label*/
      $label = $region['label'];
      $form['regions'][$region_name] = [
        '#type' => 'fieldset',
        '#title' => $label->render() . ' region',
        '#tree' => TRUE,
      ];
      $options = [0, 2, 3, 4, 5, 6, 8, 9, 12];
      $form['regions'][$region_name]['width'] = [
        '#type' => 'select',
        '#title' => $this->t('Width'),
        '#default_value' => $this->configuration['regions'][$region_name]['width'],
        '#description' => $this->t('Select the width of the region out of 12 in the row. Leave 0 to not render the region.'),
        '#options' => array_combine($options, $options),
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $regions = $this->getPluginDefinition()->getRegionNames();
    $width = 0;
    foreach ($regions as $region) {
      $width += (int) $form_state->getValue(['regions', $region, 'width']);
    }
    if ($width > 12) {
      $form_state->setError($form, $this->t('The total width of the regions should not exceed 12.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Set the settings for the regions.
    $regions = $this->getPluginDefinition()->getRegionNames();
    foreach ($regions as $region) {
      $width = $form_state->getValue(['regions', $region, 'width']);
      $this->configuration['regions'][$region]['show'] =
        $width == 0 ?: 1;
      $this->configuration['regions'][$region]['width'] = $width;
    }
  }

}
