<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_list_pages_test\EventSubscriber;

use Drupal\oe_list_pages\ListPageEvents;
use Drupal\oe_list_pages\ListPageSortAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OpenEuropa List Pages test event subscriber.
 */
class ListPagesTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ListPageEvents::ALTER_SORT_OPTIONS => ['onSortOptionsAlter'],
    ];
  }

  /**
   * Event handler for altering the sort options.
   *
   * @param \Drupal\oe_list_pages\ListPageSortAlterEvent $event
   *   The event.
   */
  public function onSortOptionsAlter(ListPageSortAlterEvent $event): void {
    $options = $event->getOptions();
    $options['title__ASC'] = 'A-Z';
    $options['title__DESC'] = 'Z-A';
    $event->setOptions($options);
  }

}
