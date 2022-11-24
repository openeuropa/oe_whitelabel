<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;

/**
 * Format a field group using the inpage navigation pattern.
 *
 * @FieldGroupFormatter(
 *   id = "oe_whitelabel_helper_inpage_navigation_pattern",
 *   label = @Translation("Inpage navigation pattern"),
 *   description = @Translation("Format a field group using the inpage navigation pattern."),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
class InpageNavigationPattern extends PatternFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getPatternId(): string {
    return 'inpage_navigation';
  }

  /**
   * {@inheritdoc}
   */
  protected function getFields(array &$element, $rendering_object): ?array {
    $fields = [];
    $visible_displays = ['above', 'inline'];

    foreach (Element::children($element) as $delta => $field_name) {
      $field_element = $element[$field_name];
      $fields['content'][$delta] = $field_element;

      if (!array_key_exists('#title', $field_element)) {
        continue;
      }

      // If the label is not configured for display, do not add this field to
      // the navigation entries.
      if (!in_array($field_element['#label_display'] ?? NULL, $visible_displays)) {
        continue;
      }

      $field_label = $field_element['#title'];
      $id = $field_element['#title_attributes']['id'] ?? NULL;
      // Generate an ID for the title, if one doesn't exist yet.
      if ($id === NULL) {
        $id = Html::getUniqueId(Html::cleanCssIdentifier($field_label));
        $fields['content'][$delta]['#title_attributes']['id'] = $id;
      }

      $fields['links'][] = [
        'label' => $field_label,
        'path' => '#' . $id,
      ];
    }

    // Use the field group label as title.
    if (!empty($fields['links'])) {
      $fields['title'] = $this->getLabel();
    }

    return $fields;
  }

}
