<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The Slim Select version bundled by the parent theme.
   */
  private const BUNDLED_VERSION = 'v3.4.3';

  /**
   * Constructs a SlimSelectHelper object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $configFactory
   *   The configuration factory.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
    protected ?ConfigFactoryInterface $configFactory = NULL,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('config.factory'),
    );
  }

  /**
   * Alter requirements for the Slim Select.
   *
   * @param array $requirements
   *   The list of requirements.
   */
  public function alterRequirements(array &$requirements): void {
    $js_file_path = $this->getJsFilePath();
    if (isset($requirements['slim_select_library']) && $js_file_path !== '') {
      $requirements['slim_select_library'] = [
        'title' => t('Slim Select library'),
        'severity' => REQUIREMENT_OK,
        'value' => t('Library available at :path.', [
          ':path' => $js_file_path,
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
    $js_file_path = $this->getJsFilePath();
    if ($js_file_path === '') {
      return;
    }
    $libraries['slim.select']['js'] = [
      $js_file_path => [
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
    $version = $this->configFactory
      ? $this->configFactory->get('slim_select.settings')->get('version')
      // Keep the previous one-argument constructor usable for consumers that
      // instantiate the helper directly.
      // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
      : \Drupal::config('slim_select.settings')->get('version');
    if ($version !== self::BUNDLED_VERSION) {
      return '';
    }

    $theme_name = 'oe_bootstrap_theme';
    if ($this->themeHandler->themeExists($theme_name)) {
      $theme_path = $this->themeHandler->getTheme($theme_name)->getPath();
      return '/' . $theme_path . '/assets/js/slimselect.min.js';
    }

    return '';
  }

}
