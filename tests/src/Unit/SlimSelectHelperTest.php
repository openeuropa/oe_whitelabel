<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\oe_whitelabel\SlimSelectHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Slim Select helper.
 *
 * @group oe_whitelabel
 */
class SlimSelectHelperTest extends UnitTestCase {

  /**
   * Tests that the matching canonical theme asset overrides the module library.
   */
  public function testLibraryInfoAlter(): void {
    $libraries = $this->getLibraries();
    $helper = $this->createHelper('v3.6.1');
    $helper->libraryInfoAlter($libraries, 'slim_select');

    $expected_path = '/themes/contrib/oe_bootstrap_theme/assets/js/slimselect.min.js';
    $this->assertSame([
      $expected_path => [
        'minified' => TRUE,
        'attributes' => [
          'defer' => TRUE,
        ],
      ],
    ], $libraries['slim.select']['js']);
    $this->assertArrayNotHasKey('css', $libraries['slim.select']);
  }

  /**
   * Tests that other configured versions remain managed by the module.
   */
  public function testOtherVersionsArePreserved(): void {
    foreach (['v1.27.1', 'v2.10.0', 'v3.3.0', 'v3.4.3', 'v4.0.0', NULL] as $version) {
      $libraries = $this->getLibraries();
      $expected = $libraries;
      $helper = $this->createHelper($version);
      $helper->libraryInfoAlter($libraries, 'slim_select');

      $this->assertSame($expected, $libraries, 'Failed for ' . ($version ?? 'no configured version'));

      $requirements = ['slim_select_library' => ['value' => 'Module result']];
      $expected_requirements = $requirements;
      $helper->alterRequirements($requirements);
      $this->assertSame($expected_requirements, $requirements);
    }
  }

  /**
   * Tests that the library is preserved when the parent theme is unavailable.
   */
  public function testMissingParentThemePreservesLibrary(): void {
    $libraries = $this->getLibraries();
    $expected = $libraries;
    $helper = $this->createHelper('v3.6.1', FALSE);
    $helper->libraryInfoAlter($libraries, 'slim_select');

    $this->assertSame($expected, $libraries);
  }

  /**
   * Creates the helper for a configured Slim Select version.
   *
   * @param string|null $version
   *   The configured Slim Select version.
   * @param bool $theme_exists
   *   Whether the parent theme exists.
   *
   * @return \Drupal\oe_whitelabel\SlimSelectHelper
   *   The helper.
   */
  private function createHelper(?string $version, bool $theme_exists = TRUE): SlimSelectHelper {
    $theme = $this->createMock(Extension::class);
    $theme->method('getPath')
      ->willReturn('themes/contrib/oe_bootstrap_theme');

    $theme_handler = $this->createMock(ThemeHandlerInterface::class);
    $theme_handler->method('themeExists')
      ->with('oe_bootstrap_theme')
      ->willReturn($theme_exists);
    $theme_handler->method('getTheme')
      ->with('oe_bootstrap_theme')
      ->willReturn($theme);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('version')
      ->willReturn($version);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('slim_select.settings')
      ->willReturn($config);

    return new SlimSelectHelper($theme_handler, $config_factory);
  }

  /**
   * Returns an unaltered Slim Select library definition.
   *
   * @return array
   *   The library definitions.
   */
  private function getLibraries(): array {
    return [
      'slim.select' => [
        'js' => ['https://example.com/slimselect.js' => []],
        'css' => ['component' => ['https://example.com/slimselect.css' => []]],
      ],
    ];
  }

}
