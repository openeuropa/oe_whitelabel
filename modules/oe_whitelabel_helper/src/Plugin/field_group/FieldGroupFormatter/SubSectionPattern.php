<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

/**
 * Format a field group using a section pattern.
 *
 * @FieldGroupFormatter(
 *   id = "oe_whitelabel_sub_section_pattern",
 *   label = @Translation("Sub-section pattern"),
 *   description = @Translation("Format a field group using the section pattern with h3 title tag and less spacing."),
 *   supported_contexts = {
 *     "view",
 *   }
 * )
 */
class SubSectionPattern extends PreRenderingFieldGroupFormatterBase {

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
      '#settings' => [
        'heading_tag' => 'h3',
      ],
      '#attributes' => $properties['#attributes'] ?? [],
    ];
    $element['#attributes']['class'][] = 'mb-4';
    return $element;
  }

}
