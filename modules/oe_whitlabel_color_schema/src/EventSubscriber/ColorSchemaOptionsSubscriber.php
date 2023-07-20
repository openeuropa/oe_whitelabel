<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_color_schema\EventSubscriber;

use Drupal\oe_whitelabel_color_schema\Event\ColorSchemaOptionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides options for the color schema fields.
 *
 * @see \Drupal\oe_paragraphs\EventSubscriber\OptionsSubscriber
 * @see _oe_whitelabel_allowed_values_color_schema()
 *
 * @todo Put this into oe_showcase.
 */
class ColorSchemaOptionsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ColorSchemaOptionsEvent::class => ['getColorSchemaOptions', -1],
    ];
  }

  /**
   * Gets the icon options.
   *
   * @param \Drupal\oe_whitelabel_color_schema\Event\ColorSchemaOptionsEvent $event
   *   Allowed format event object.
   */
  public function getColorSchemaOptions(ColorSchemaOptionsEvent $event): void {
    $event->setColorSchemaOptions([
      'oewt-color-schema-red' => 'Red',
      'oewt-color-schema-blue' => 'Blue',
    ]);
  }

}
