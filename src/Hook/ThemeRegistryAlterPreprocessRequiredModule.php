<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel\Hook;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\oe_whitelabel\Attribute\RequiredModule;

/**
 * Removes preprocess hooks for disabled modules based on an attribute.
 *
 * @see \Drupal\oe_whitelabel\Attribute\RequiredModule
 */
class ThemeRegistryAlterPreprocessRequiredModule {

  use AutowireTrait;

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_theme_registry_alter().
   *
   * @param array $registry
   *   The theme registry.
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$registry): void {
    foreach ($registry as $hook => $info) {
      if (!isset($info['preprocess functions'])) {
        continue;
      }
      foreach ($info['preprocess functions'] as $delta => $function) {
        if ($this->shouldFunctionBeRemoved($function)) {
          unset($registry[$hook]['preprocess functions'][$delta]);
        }
      }
    }
  }

  /**
   * Determines if a theme function should be removed.
   *
   * @param mixed $function
   *   An entry from ['preprocess functions'] of a theme hook info.
   *   This should be a string function name. But in future versions of Drupal,
   *   in some cases it might be something else.
   *
   * @return bool
   *   TRUE if the preprocess function should be removed.
   */
  protected function shouldFunctionBeRemoved(mixed $function): bool {
    if (
      !is_string($function) ||
      // Currently this mechanism is restricted to oe_whitelabel_preprocess_*.
      // The goal is to avoid that custom code will rely on this feature for
      // their own preprocess functions.
      !str_starts_with($function, 'oe_whitelabel_preprocess_') ||
      !function_exists($function)
      // The function_exists() check depends on which files are included at the
      // time this runs.
    ) {
      return FALSE;
    }
    $reflection_attributes = (new \ReflectionFunction($function))->getAttributes(RequiredModule::class);
    if (!$reflection_attributes) {
      return FALSE;
    }
    foreach ($reflection_attributes as $reflection_attribute) {
      $attribute = $reflection_attribute->newInstance();
      assert($attribute instanceof RequiredModule);
      if (!$this->moduleHandler->moduleExists($attribute->module)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
