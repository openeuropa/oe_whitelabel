<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_modal_page\Hook;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\oe_whitelabel_modal_page\Form\ModalForm;

/**
 * Contains hook implementations that alter the 'modal' entity type.
 */
class ModalEntityTypeFormClass {

  /**
   * Implements hook_entity_type_alter().
   *
   * Sets custom form classes for the 'modal' entity type.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    $modal_entity_type = $entity_types['modal'] ?? NULL;
    if ($modal_entity_type instanceof ConfigEntityType) {
      // Override modal_page module's form for editing modals.
      $modal_entity_type->setFormClass('add', ModalForm::class);
      $modal_entity_type->setFormClass('edit', ModalForm::class);
    }
  }

}
