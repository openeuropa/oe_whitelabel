<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\oe_link_lists\LinkInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to apply overrides to an entity before rendering.
 */
class EntityViewDisplayEntityOverridesEvent extends Event {

  /**
   * Creates a new instance of this event.
   *
   * @param \Drupal\oe_link_lists\LinkInterface $link
   *   The link list link entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that will be rendered.
   */
  public function __construct(protected LinkInterface $link, protected ContentEntityInterface $entity) {}

  /**
   * Returns the link.
   *
   * @return \Drupal\oe_link_lists\LinkInterface
   *   The link entity.
   */
  public function getLink(): LinkInterface {
    return $this->link;
  }

  /**
   * Returns the entity that will be rendered.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Sets the entity.
   *
   * Please pay attention that the entity set here will be prevented from
   * being saved via oe_whitelabel_link_lists_entity_presave().
   * If the entity is being replaced, make sure it's a "clone" of the original
   * like it's done in EntityViewDisplayPluginBase::buildItems().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function setEntity(ContentEntityInterface $entity): void {
    $this->entity = $entity;
  }

}
