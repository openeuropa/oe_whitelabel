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
   * @param bool $background
   *   Add a background class if TRUE.
   */
  public function injectColorScheme(array &$variables, bool $background = TRUE): void {
    if (!isset($variables['elements']['#oe_color_scheme'])) {
      return;
    }

    $variables['attributes']['class'][] = $variables['elements']['#oe_color_scheme'];
    $variables['attributes']['class'][] = 'text-color-default';

    if ($background) {
      $variables['attributes']['class'][] = 'bg-default';
    }
  }

}
