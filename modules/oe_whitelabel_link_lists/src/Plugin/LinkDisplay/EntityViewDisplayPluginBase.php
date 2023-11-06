<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\EntityAwareLinkInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for link display plugins that use entity view modes.
 *
 * Links that refer to entities will use the view mode, if available, or render
 * the link with a fallback.
 */
abstract class EntityViewDisplayPluginBase extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creates a new instance of this plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

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
    foreach ($links as $link) {
      /** @var \Drupal\oe_link_lists\LinkInterface $link */
      $entity = $link instanceof EntityAwareLinkInterface ? $link->getEntity() : NULL;

      if (!$entity) {
        $items[] = $this->buildLinkWithFallback($link);
        continue;
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Check if the entity type has the view display available.
      $entity_type_id = $entity->getEntityTypeId();
      $view_display_id = $this->getPluginId();

      $view_display = $display_storage->load(implode('.', [
        $entity_type_id,
        $entity->bundle(),
        $view_display_id,
      ]));

      if (!$view_display) {
        $items[] = $this->buildLinkWithFallback($link);
        continue;
      }

      $items[] = [
        'entity' => $this->entityTypeManager->getViewBuilder($entity_type_id)->view($entity, $view_display_id),
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
