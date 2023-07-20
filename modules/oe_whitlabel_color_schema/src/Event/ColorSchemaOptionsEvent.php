<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_color_schema\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered when a color schema's options need to be provided.
 */
class ColorSchemaOptionsEvent extends Event {

  /**
   * The array containing the allowed values.
   *
   * @var array
   */
  protected $colorSchemaOptions = [];

  /**
   * Sets the color schema options list.
   *
   * @param array $color_schema_options
   *   Array containing the set of allowed values for color schema options.
   */
  public function setColorSchemaOptions(array $color_schema_options = []): void {
    $this->colorSchemaOptions = $color_schema_options;
  }

  /**
   * Gets the color schema option list values.
   *
   * @return array
   *   Array containing the set of allowed values for color schema options.
   */
  public function getColorSchemaOptions(): array {
    return $this->colorSchemaOptions;
  }

}
