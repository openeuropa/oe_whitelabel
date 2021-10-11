<?php

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "string_data_provider",
 *   label = @Translation("String data provider"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class StringDataProviderFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $element = [
      '#fields' => [
        'content' => $items[0]->value,
        // @TODO: Set these values in config form.
        'tag' => 'h5',
        'classes' => 'mb-4',
      ]
    ];

    return $element;
  }
}
