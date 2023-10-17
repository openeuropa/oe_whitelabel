<?php

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityViewDisplayDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}


  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }


  public function getDerivativeDefinitions($base_plugin_definition) {
    // @todo Fetch all enabled entity types, or limit to nodes.
    return $this->derivatives;
    foreach ($this->getEnabledEntityViewDisplays() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $label) {
        $derivative_id = implode(':', [
          'entity_view_display',
          $entity_type_id,
          $bundle
        ]);

        $this->derivatives[$derivative_id] = [
          'label' => $label,
        ] + $base_plugin_definition;
      }
    }


    return $this->derivatives;
  }

  protected function getEnabledEntityViewDisplays(): array {
    $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $displays = $view_display_storage->getQuery()
      ->condition('targetEntityType', 'node')
      ->condition('status', TRUE)
      ->condition('third_party_settings.oe_whitelabel_link_lists.link_display', TRUE)
      ->accessCheck()
      ->execute();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays */
    $displays = $view_display_storage->loadMultiple($displays);
    $view_mode_storage = $this->entityTypeManager->getStorage('entity_view_mode');
    $options = [];
    foreach ($displays as $display) {
      $options['node'][$display->getTargetBundle()][$display->getMode()] = $display->getMode() === 'default' ? $this->t('Default') : $view_mode_storage->load('node' . '.' . $display->getMode())->label();
    }

    return $options;
  }

}
