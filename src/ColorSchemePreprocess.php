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
   * @param array $options
   *   An associative array of options to control the behavior of the function.
   *   Possible keys are:
   *   - 'text_colored': (bool) If set to TRUE, adds a class to override the
   *     text color.
   *   - 'background': (string) The name of the background variant, ex: default,
   *   primary, secondary, danger, etc.
   *
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function injectColorScheme(array &$variables, array $options = []): void {
    if (!isset($variables['elements']['#color_scheme_field'])) {
      return;
    }

    $default_options = [
      'text_colored' => FALSE,
      'background' => '',
    ];

    $options = $options + $default_options;

    $variables['attributes'] = new Attribute($variables['attributes'] ?? []);
    $variables['attributes']->addClass(Html::getClass($variables['elements']['#color_scheme_field']));

    if ($options['text_colored'] === TRUE) {
      $variables['attributes']->addClass('text-color-default');
    }

    match ($options['background']) {
      'default' => $variables['attributes']->addClass('bg-default'),
      'primary' => $variables['attributes']->addClass('text-bg-primary'),
      'secondary' => $variables['attributes']->addClass('text-bg-secondary'),
      'danger' => $variables['attributes']->addClass('text-bg-danger'),
      'success' => $variables['attributes']->addClass('text-bg-success'),
      default => FALSE,
    };
  }

}
