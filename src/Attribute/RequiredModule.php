<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel\Attribute;

/**
 * Removes a preprocess function if a given module is not installed.
 *
 * @see \oe_whitelabel_theme_registry_alter()
 * @see \Drupal\oe_whitelabel\Hook\ThemeRegistryAlterPreprocessRequiredModule
 *
 * @internal
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
final class RequiredModule {

  /**
   * Constructs a new instance.
   *
   * @param string $module
   *   A module machine name.
   */
  public function __construct(
    public readonly string $module,
  ) {}

}
