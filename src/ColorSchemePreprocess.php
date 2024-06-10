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

    // The text-bg- classes already set the text and background color,
    // so we remove the others.
    if ($options['primary_background']) {
      $variables['attributes']->addClass('text-bg-primary');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['secondary_background']) {
      $variables['attributes']->addClass('text-bg-secondary');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['danger_background']) {
      $variables['attributes']->addClass('text-bg-danger');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }

    if ($options['success_background']) {
      $variables['attributes']->addClass('text-bg-success');
      $variables['attributes']->removeClass(['bg-default', 'text-color-default']);
    }
  }

}
