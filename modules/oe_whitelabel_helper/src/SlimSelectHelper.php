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
  public function alterRequirements(array &$requirements): void {
    if (isset($requirements['slim_select_library'])) {
      $requirements['slim_select_library'] = [
        'title' => t('Slim Select library'),
        'severity' => REQUIREMENT_OK,
        'value' => t('Library available at :path.', [
          ':path' => $this->getJsFilePath(),
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
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ('slim_select' !== $extension) {
      return;
    }
    $libraries['slim.select']['js'] = [
      $this->getJsFilePath() => [
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
  private function getJsFilePath(): string {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_path = $theme_handler->getTheme('oe_bootstrap_theme')->getPath();
    $version = \Drupal::config('slim_select.settings')->get('version');
    if (str_starts_with($version, 'v2.')) {
      return '/' . $theme_path . '/assets/js/slim-select-2/slimselect.min.js';
    }
    return '/' . $theme_path . '/assets/js/slimselect.min.js';
  }

}
