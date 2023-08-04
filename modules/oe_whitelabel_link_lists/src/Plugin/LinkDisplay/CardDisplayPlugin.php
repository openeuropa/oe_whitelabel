<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\oe_link_lists\EntityAwareLinkInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Card display of link lists.
 *
 * @LinkDisplay(
 *   id = "card",
 *   label = @Translation("Card"),
 *   description = @Translation("Display a Link lists using Card view display."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class CardDisplayPlugin extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ViewModeDisplayPlugin constructor.
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
   * {@inheritdoc}
   */
  public function buildItems(LinkCollectionInterface $links): array {
    /** @var \Drupal\oe_link_lists\LinkInterface $link */
    foreach ($links as $link) {
      $entity = $link instanceof EntityAwareLinkInterface ? $link->getEntity() : NULL;

      if (!$entity) {
        // Skip invalid entities.
        continue;
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Check if the entity type has the view display available.
      $entity_type_id = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $view_display_id = $this->pluginId;
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $view_display = $storage->load($entity_type_id . '.' . $bundle . '.' . $view_display_id);

      if (!$view_display) {
        $link_here = Link::fromTextAndUrl($this->t('here'),
          Url::fromUri('internal:' . '/admin/structure/types/manage/' . $entity->bundle() . '/display#edit-modes')
        )->toString();
        $message = new FormattableMarkup('The <b>@view_display</b> view mode is not available for <b>@bundle</b>. Please enable it @link_here', [
          '@view_display' => $view_display_id,
          '@bundle' => $entity->bundle(),
          '@link_here' => $link_here,
        ]);
        \Drupal::messenger()->addError($message);
        return [];
      }
      $renderable_array = $this->entityTypeManager->getViewBuilder($entity_type_id)->view($entity, $view_display_id);

      $items[] = ['content' => $renderable_array];
    }
    return $items;
  }

}
