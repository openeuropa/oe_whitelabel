<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

/**
 * Format a field group using a section pattern.
 *
 * @FieldGroupFormatter(
 *   id = "oe_whitelabel_section_pattern",
 *   label = @Translation("Section pattern"),
 *   description = @Translation("Format a field group using the section pattern."),
 *   supported_contexts = {
 *     "view",
 *   }
 * )
 */
class SectionPattern extends PreRenderingFieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $child_elements, array $properties): array {
    $element = [
      '#type' => 'pattern',
      '#id' => 'section',
      '#fields' => [
        'heading' => $this->getLabel(),
        'content' => $child_elements,
      ],
      '#attributes' => $properties['#attributes'] ?? [],
    ];
    $element['#attributes']['class'][] = 'mb-5';
    return $element;
  }

}
