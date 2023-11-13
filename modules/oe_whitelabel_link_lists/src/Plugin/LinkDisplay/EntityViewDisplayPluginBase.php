<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\EntityAwareLinkInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkInterface;
use Drupal\oe_whitelabel_link_lists\Event\EntityViewDisplayEntityOverridesEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for link display plugins that use entity view modes.
 *
 * Links that refer to entities will use the view mode, if available, or render
 * the link with a fallback.
 */
abstract class EntityViewDisplayPluginBase extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a new instance of this plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EventDispatcherInterface $eventDispatcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Returns the ID of the entity display mode.
   *
   * @return string
   *   The entity display mode ID.
   */
  abstract protected function getEntityDisplayModeId(): string;

  /**
   * Builds items.
   *
   * @param \Drupal\oe_link_lists\LinkCollectionInterface $links
   *   Links to be added in each column.
   *
   * @return array
   *   The renderable array.
   */
  protected function buildItems(LinkCollectionInterface $links): array {
    $items = [];

    $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $view_display_id = $this->getEntityDisplayModeId();

    foreach ($links as $link) {
      /** @var \Drupal\oe_link_lists\LinkInterface $link */
      $entity = $link instanceof EntityAwareLinkInterface ? $link->getEntity() : NULL;

      if (!$entity) {
        $items[] = $this->buildLinkWithFallback($link);
        continue;
      }

      // @todo Isn't this handled by oe_link_lists?
      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Check if the entity type has the view display available.
      $entity_type_id = $entity->getEntityTypeId();
      $view_display = $display_storage->load(implode('.', [
        $entity_type_id,
        $entity->bundle(),
        $view_display_id,
      ]));

      if (!$view_display) {
        $items[] = $this->buildLinkWithFallback($link);
        continue;
      }

      // Create a new instance of the entity, so that changes to this entity
      // won't be applied to the original entity.
      // E.g. if we change the title of this entity, we don't want the main
      // object, that is cached in the storage static cache, to be changed.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $overridable_entity */
      $overridable_entity = $this->entityTypeManager->getStorage($entity_type_id)
        ->create($entity->toArray());
      // The create() method sets enforceIsNew() to true, but this prevents
      // generating things like URL of the entity.
      // @see template_preprocess_node()
      // For the same reason, we cannot use EntityInterface::createDuplicate()
      // as it will unset the ID, UUID and revision fields.
      // @see \Drupal\Core\Entity\EntityInterface::createDuplicate
      $overridable_entity->enforceIsNew(FALSE);

      // Mark the entity as override created by this plugin.
      /** @var \WeakMap $list */
      $list = drupal_static('oe_whitelabel_link_lists.weak_map', new \WeakMap());
      $list[$overridable_entity] = $this->getPluginId();

      $event = new EntityViewDisplayEntityOverridesEvent($link, $overridable_entity);
      $this->eventDispatcher->dispatch($event);
      $overridable_entity = $event->getEntity();

      // The entity might have been changed in the event, so we add it again.
      $list[$overridable_entity] = $this->getPluginId();

      $items[] = [
        'entity' => $this->entityTypeManager->getViewBuilder($entity_type_id)->view($overridable_entity, $view_display_id),
      ];
    }
    return $items;
  }

  /**
   * Builds a link with a fallback rendering mode, e.g. a pattern.
   *
   * @param \Drupal\oe_link_lists\LinkInterface $link
   *   The link list link.
   *
   * @return array
   *   The render array.
   */
  abstract protected function buildLinkWithFallback(LinkInterface $link): array;

}
