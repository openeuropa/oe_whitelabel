<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Traits;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Contains methods to assert fields in Node displays.
 */
trait NodeFieldDisplayTrait {

  /**
   * Asserts settings for a field in a node type display.
   *
   * @param array $expected
   *   An array of values to check.
   * @param string $bundle
   *   The node type bundle.
   * @param string $display
   *   The display of the node type.
   * @param string $field_name
   *   The field to check.
   * @param bool $assert_weights
   *   If weights are part of the assertion.
   */
  protected function assertSettingsNodeDisplayField(array $expected, string $bundle, string $display, string $field_name, $assert_weights = FALSE): void {
    $display = $this->getNodeViewDisplay($bundle, $display);
    $components = $display->getComponents();

    if (!isset($components[$field_name])) {
      throw new \InvalidArgumentException(sprintf('Field %s not found in content region from %s display.', $field_name, $display));
    }

    // By default we avoid weight's since it's really hard to predict this from
    // tests perspective.
    if (!$assert_weights) {
      unset($components[$field_name]['weight']);
    }

    $this->assertEquals($expected, $components[$field_name]);
  }

  /**
   * Asserts that a field is hidden in a node type display.
   *
   * @param string $bundle
   *   The node type bundle.
   * @param string $display
   *   The display of the node type.
   * @param string $field_name
   *   The field to check.
   */
  protected function assertHiddenNodeDisplayField(string $bundle, string $display, string $field_name): void {
    $display = $this->getNodeViewDisplay($bundle, $display);

    $properties = $display->toArray();
    $this->assertTrue(isset($properties['hidden'][$field_name]));
  }

  /**
   * Provides the display for the given node type.
   *
   * @param string $bundle
   *   The node type bundle.
   * @param string $display
   *   The display of the node type.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The display object.
   */
  private function getNodeViewDisplay(string $bundle, string $display): EntityViewDisplayInterface {
    $display = \Drupal::service('entity_type.manager')->getStorage('entity_view_display')->load('node.' . $bundle . '.' . $display);

    if (empty($display)) {
      throw new \InvalidArgumentException(sprintf('The display %s for the node type $s could not be found.', $bundle, $display));
    }

    return $display;
  }

}
