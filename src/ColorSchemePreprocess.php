<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel;

/**
 * Class containing color scheme preprocesses.
 */
class ColorSchemePreprocess {

  /**
   * Injects the color scheme value into the render array if available.
   *
   * @param array $variables
   *   The render array.
   */
  public function injectColorScheme(array &$variables): void {
    if (!isset($variables['elements']['#oe_color_scheme'])) {
      return;
    }

    $variables['attributes']['class'][] = $variables['elements']['#oe_color_scheme'];
  }

}
