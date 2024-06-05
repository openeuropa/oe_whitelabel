<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Special base class for field group formatters.
 *
 * This is meant for field group formatters that want their logic to run in the
 * '#pre_render' step, when the final list of visible fields has been correctly
 * determined.
 *
 * @see \field_group_remove_empty_display_groups()
 */
abstract class PreRenderingFieldGroupFormatterBase extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $element['#pre_render'][] = [$this, 'doPreRender'];
  }

  /**
   * Actual '#pre_render' callback.
   *
   * This is called _after_ field_group has removed empty elements.
   *
   * @param array $element
   *   Original render element.
   *
   * @return array
   *   Processed render element.
   *
   * @see \field_group_remove_empty_display_groups()
   */
  #[TrustedCallback]
  public function doPreRender(array $element): array {
    $child_keys = Element::children($element);
    $children = array_map(
      fn (string|int $key) => $element[$key],
      array_combine($child_keys, $child_keys),
    );
    $properties = array_diff_key($element, $children);
    $content = $this->buildContent($children, $properties);
    return ['content' => $content] + $properties;
  }

  /**
   * Builds a new render element for the field group.
   *
   * This will be added as a child in the original render element, replacing all
   * the existing children. This allows a new '#pre_render' cycle to start on
   * the new element.
   *
   * @param array<array> $child_elements
   *   Child elements extracted from the original field group element, with none
   *   of the keys that start with '#'.
   * @param array $properties
   *   Element properties extracted from the original field group element, with
   *   each key starting with '#'.
   *
   * @return array
   *   New render element.
   */
  abstract protected function buildContent(array $child_elements, array $properties): array;

}
