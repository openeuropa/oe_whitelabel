<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slim Select related helper methods.
 */
class SlimSelectHelper implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a SlimSelectHelper object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(protected ThemeHandlerInterface $themeHandler) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
    );
  }

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
    $theme_name = 'oe_bootstrap_theme';
    if ($this->themeHandler->themeExists($theme_name)) {
      $theme_path = $this->themeHandler->getTheme($theme_name)->getPath();
      $version = \Drupal::config('slim_select.settings')->get('version');
      if ($version && str_starts_with($version, 'v2.')) {
        return '/' . $theme_path . '/assets/js/slim-select-2/slimselect.min.js';
      }
      return '/' . $theme_path . '/assets/js/slimselect.min.js';
    }

    return '';
  }

}
