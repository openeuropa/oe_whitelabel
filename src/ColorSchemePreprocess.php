<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

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
  public function injectColorScheme(array &$variables, array $options = []): void {
    if (!isset($variables['elements']['#oe_color_scheme'])) {
      return;
    }

    $variables['attributes'] = new Attribute($variables['attributes'] ?? []);
    $variables['attributes']->addClass(Html::getClass($variables['elements']['#oe_color_scheme']));

    if ($options['text_colored']) {
      $variables['attributes']->addClass('text-color-default');
    }

    if ($options['background']) {
      $variables['attributes']->addClass('bg-default');
    }

    if ($options['primary_background']) {
      $variables['attributes']->addClass('text-bg-primary');
      // The text-bg- class already sets the text and background color.
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }
  }

}
