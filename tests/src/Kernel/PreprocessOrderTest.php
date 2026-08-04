<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the order of preprocess functions.
 *
 * See https://www.drupal.org/project/drupal/issues/3593583.
 */
class PreprocessOrderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_revisions',
    'paragraphs',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service(ThemeInstallerInterface::class)->install([
      'stark',
      'oe_whitelabel',
      'oe_whitelabel_test_subtheme',
    ]);
  }

  /**
   * Tests the order and completeness of preprocess functions.
   */
  public function testPreprocessOrder(): void {
    $this->setTheme('stark');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
    ], 'paragraph');
    $this->assertThemeHookNotExists('paragraph__oe_accordion');
    $this->assertThemeHookNotExists('paragraph__oe_gallery');

    $this->setTheme('oe_whitelabel');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph__oe_accordion');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph__oe_gallery');

    $this->setTheme('oe_whitelabel_test_subtheme');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
      'oe_whitelabel_test_subtheme_preprocess_paragraph__oe_accordion',
    ], 'paragraph__oe_accordion');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph__oe_gallery');

    $this->enableModules(['oe_whitelabel_paragraphs']);

    $this->setTheme('stark');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
    ], 'paragraph');
    $this->assertThemeHookNotExists('paragraph__oe_accordion');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      // This preprocess is still active when 'stark' is the active theme.
      'oe_whitelabel_paragraphs_preprocess_paragraph__oe_gallery',
    ], 'paragraph__oe_gallery');

    $this->setTheme('oe_whitelabel');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph');
    $this->assertPreprocessFunctions($base_preprocess = [
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
      'oe_whitelabel_preprocess_paragraph__oe_accordion',
    ], 'paragraph__oe_accordion');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
      'oe_whitelabel_paragraphs_preprocess_paragraph__oe_gallery',
    ], 'paragraph__oe_gallery');

    $this->setTheme('oe_whitelabel_test_subtheme');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
    ], 'paragraph');
    $this->assertPreprocessFunctions($base_preprocess = [
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
      'oe_whitelabel_preprocess_paragraph__oe_accordion',
      'oe_whitelabel_test_subtheme_preprocess_paragraph__oe_accordion',
    ], 'paragraph__oe_accordion');
    $this->assertPreprocessFunctions([
      'template_preprocess_paragraph',
      'oe_bootstrap_theme_preprocess',
      'oe_whitelabel_preprocess',
      'oe_whitelabel_paragraphs_preprocess_paragraph__oe_gallery',
    ], 'paragraph__oe_gallery');
  }

  /**
   * Sets the active theme and resets the registry.
   *
   * @param string $theme
   *   Theme name to set as the active theme.
   */
  protected function setTheme(string $theme): void {
    $this->config('system.theme')->set('default', $theme)->save();
    // This causes a new theme registry.
    \Drupal::service(ThemeManagerInterface::class)->resetActiveTheme();
  }

  /**
   * Asserts that a theme hook does not exist in the current registry.
   *
   * @param string $hook
   *   The theme hook.
   */
  protected function assertThemeHookNotExists(string $hook): void {
    $info = \Drupal::service(Registry::class)->get()[$hook] ?? NULL;
    $this->assertNull($info);
  }

  /**
   * Asserts preprocess functions in a theme hook.
   *
   * @param list<string> $expected
   *   Expected preprocess functions.
   * @param string $hook
   *   The hook name.
   */
  protected function assertPreprocessFunctions(array $expected, string $hook): void {
    $info = \Drupal::service(Registry::class)->get()[$hook] ?? NULL;
    $this->assertNotNull($info);
    if (version_compare(\Drupal::VERSION, '11.0', '<')) {
      $expected = ['template_preprocess', ...$expected];
    }
    $preprocess_functions = $info['preprocess functions'] ?? [];
    // Normalize 'initial preprocess' (OOP hooks) to the legacy function name,
    // unless it is already present (older Drupal core).
    $legacy_initial_preprocess = 'template_preprocess_' . ($info['base hook'] ?? $hook);
    $actual = [];
    if (!empty($info['initial preprocess'])) {
      // Confirm it is a real callback.
      $callable = $info['initial preprocess'];
      if (!is_callable($callable)) {
        $callable = \Drupal::service('callable_resolver')->getCallableFromDefinition($callable);
      }
      $this->assertIsCallable($callable, "Hook '$hook' has an invalid 'initial preprocess' callback.");

      if (!in_array($legacy_initial_preprocess, $preprocess_functions, TRUE)) {
        $actual[] = $legacy_initial_preprocess;
      }
    }
    // Use '...' to normalize integer keys.
    $actual = [...$actual, ...$preprocess_functions];
    $this->assertSame($expected, $actual);
  }

}
