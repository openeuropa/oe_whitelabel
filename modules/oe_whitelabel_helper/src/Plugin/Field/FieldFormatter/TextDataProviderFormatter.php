<?php

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_data_provider",
 *   label = @Translation("Text data provider"),
 *   field_types = {
 *     "text",
 *     "text_with_summary",
 *     "text_long"
 *   }
 * )
 */
class TextDataProviderFormatter extends FormatterBase {

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
    $element = [
      '#fields' => [
        'content' => substr(strip_tags($items[0]->value), 0, 500),
        // @TODO: Set these values in config form.
        'classes' => 'mb-4',
      ],
    ];

    return $element;
  }
}
