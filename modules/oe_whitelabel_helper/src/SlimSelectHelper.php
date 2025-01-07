<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper;

/**
 * Slim Select related helper methods.
 *
 * @internal
 */
class SlimSelectHelper {

  /**
   * Alter requirements for the Slim Select.
   *
   * @param array $requirements
   *   The list of requirements.
   */
  public function alterRequirements(array &$requirements) {
    if (isset($requirements['slim_select_library'])) {
      $path = \Drupal::service('oe_whitelabel_helper.slim_select')->getJsFilePath();
      $requirements['slim_select_library'] = [
        'title' => t('Slim Select library'),
        'severity' => REQUIREMENT_OK,
        'value' => t('Library available at :path.', [
          ':path' => $path,
        ]),
      ];
    }
  }

  /**
   * Alter a library info for the SLim Select.
   *
   * @param array $libraries
   *   The list of libraries.
   * @param string $extension
   *   The extension.
   */
  public function libraryInfoAlter(array &$libraries, string $extension) {
    if ('slim_select' !== $extension) {
      return;
    }
    $path = \Drupal::service('oe_whitelabel_helper.slim_select')->getJsFilePath();
    $libraries['slim.select']['js'] = [
      $path => [
        'minified' => TRUE,
        'attributes' => [
          'defer' => TRUE,
        ],
      ],
    ];
    // The slim.select css is already present in the parent theme.
    unset($libraries['slim.select']['css']);
  }

  /**
   * Get the Slim Select library JS path.
   *
   * @return string
   *   The Slim Select JS path.
   */
  private function getJsFilePath() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_path = $theme_handler->getTheme('oe_bootstrap_theme')->getPath();
    $version = \Drupal::config('slim_select.settings')->get('version');
    if (str_starts_with($version, 'v2.')) {
      return '/' . $theme_path . '/assets/js/slim-select-2/slimselect.min.js';
    }
    return '/' . $theme_path . '/assets/js/slimselect.min.js';
  }

}
