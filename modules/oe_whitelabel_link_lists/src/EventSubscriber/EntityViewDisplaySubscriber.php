<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\EventSubscriber;

use Drupal\oe_whitelabel_link_lists\Event\EntityViewDisplayEntityOverridesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to events launched by entity view display plugins.
 */
class EntityViewDisplaySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityViewDisplayEntityOverridesEvent::class => 'applyLinkOverridesToEntity',
    ];
  }

  /**
   * Applies link values to the entity.
   *
   * @param \Drupal\oe_whitelabel_link_lists\Event\EntityViewDisplayEntityOverridesEvent $event
   *   The override event.
   */
  public function applyLinkOverridesToEntity(EntityViewDisplayEntityOverridesEvent $event): void {
    $link = $event->getLink();
    $entity = $event->getEntity();

    $label_key = $entity->getEntityType()->getKey('label');
    if ($label_key) {
      $entity->set($label_key, $link->getTitle());
    }

    $mapping = [
      'oe_project' => 'oe_teaser',
      'oe_sc_event' => 'oe_summary',
      'oe_sc_news' => 'oe_summary',
      'oe_sc_publication' => 'oe_summary',
    ];

    if (!isset($mapping[$entity->bundle()])) {
      return;
    }

    $teaser = $link->getTeaser();
    if (isset($teaser['#markup'])) {
      $entity->set($mapping[$entity->bundle()], $teaser['#markup']);
    }
  }

}
