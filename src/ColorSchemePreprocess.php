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
   *   - 'background': (bool) If set to TRUE, adds a class to override the
   *     background color.
   *   - 'primary_background': (bool) If set to TRUE, adds a class to override
   *     the background and text color to primary.
   *   - 'secondary_background': (bool) If set to TRUE, adds a class to override
   *     the background and text color to secondary.
   *   - 'danger_background': (bool) If set to TRUE, adds a class to override
   *     the background and text color to danger.
   *   - 'success_background': (bool) If set to TRUE, adds a class to override
   *     the background and text color to success.
   *
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function injectColorScheme(array &$variables, array $options = []): void {
    if (!isset($variables['elements']['#color_scheme_field'])) {
      return;
    }

    $default_options = [
      'text_colored' => FALSE,
      'background' => FALSE,
      'primary_background' => FALSE,
      'secondary_background' => FALSE,
      'danger_background' => FALSE,
      'success_background' => FALSE,
    ];

    if (array_diff_key($options, $default_options)) {
      throw new \InvalidArgumentException('Invalid options provided.');
    }

    $options = $options + $default_options;

    $variables['attributes'] = new Attribute($variables['attributes'] ?? []);
    $variables['attributes']->addClass(Html::getClass($variables['elements']['#color_scheme_field']));

    if ($options['text_colored'] === TRUE) {
      $variables['attributes']->addClass('text-color-default');
    }

    if ($options['background'] === TRUE) {
      $variables['attributes']->addClass('bg-default');
    }

    // The text-bg- classes already set the text and background color,
    // so we remove the others.
    if ($options['primary_background'] === TRUE) {
      $variables['attributes']->addClass('text-bg-primary');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['secondary_background'] === TRUE) {
      $variables['attributes']->addClass('text-bg-secondary');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['danger_background'] === TRUE) {
      $variables['attributes']->addClass('text-bg-danger');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['success_background'] === TRUE) {
      $variables['attributes']->addClass('text-bg-success');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }
  }

}
