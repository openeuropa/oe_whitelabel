<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_list_pages_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\oe_list_pages\ListPageEvents;
use Drupal\oe_list_pages\ListPageSourceAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the list page allowed entity types event.
 */
class ListPageAllowedTypesSubscriber implements EventSubscriberInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ListPageEvents::ALTER_ENTITY_TYPES => ['onEntityTypesAlter'],
      ListPageEvents::ALTER_BUNDLES => ['onBundlesAlter'],
    ];
  }

  /**
   * Event handler for limiting the allowed entity types.
   *
   * @param \Drupal\oe_list_pages\ListPageSourceAlterEvent $event
   *   The event object.
   */
  public function onEntityTypesAlter(ListPageSourceAlterEvent $event): void {
    $event->setEntityTypes(['node']);
  }

  /**
   * Event handler for limiting the allowed bundles.
   *
   * @param \Drupal\oe_list_pages\ListPageSourceAlterEvent $event
   *   The event object.
   */
  public function onBundlesAlter(ListPageSourceAlterEvent $event): void {
    $event->setBundles('node', ['oe_sc_news', 'oe_sc_event']);
  }

}
