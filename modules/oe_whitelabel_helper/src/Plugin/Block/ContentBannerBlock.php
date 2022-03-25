<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a view mode block.
 *
 * @Block(
 *   id = "oe_w_content_banner",
 *   admin_label = @Translation("Content banner"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 *   category = "Hello",
 *   entity_key = "node",
 * )
 */
class ContentBannerBlock extends BlockBase {

  /**
   * View mode name that exists both for 'user' and 'node'.
   *
   * @var string
   */
  protected string $viewModeName = 'oe_w_content_banner';

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account): AccessResult {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getContextValue('node');

    /** @var \Drupal\Core\Entity\EntityViewModeInterface|null $view_mode_object */
    $view_mode_object = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $node->bundle() . '.' . $this->viewModeName);

    return AccessResult::allowedIf($view_mode_object !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getContextValue('node');
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    return $view_builder->view($entity, $this->viewModeName);
  }

}
