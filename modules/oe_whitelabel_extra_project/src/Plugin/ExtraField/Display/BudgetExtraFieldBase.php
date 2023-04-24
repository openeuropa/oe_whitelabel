<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_extra_project\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for budget fields.
 */
abstract class BudgetExtraFieldBase extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilder
   */
  protected $viewBuilder;

  /**
   * BudgetExtraFieldBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewBuilder = $entity_type_manager->getViewBuilder('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $field_budget = $this->getLegacyBudgetFieldName();
    $field_eu_budget = $this->getBudgetFieldName();

    if ($entity->get($field_budget)->isEmpty() && $entity->get($field_eu_budget)->isEmpty()) {
      return [];
    }

    $build = [];
    $display_options = [
      'label' => 'hidden',
      'type' => 'number_decimal',
      'settings' => [
        'thousand_separator' => '.',
        'decimal_separator' => ',',
        'scale' => 2,
        'prefix_suffix' => TRUE,
      ],
    ];

    $field_name = $entity->get($field_eu_budget)->isEmpty() ? $field_budget : $field_eu_budget;
    $build[] = $this->viewBuilder->viewField($entity->get($field_name), $display_options);

    return $build;
  }

  /**
   * Returns the old budget field name.
   *
   * @return string
   *   The budget field.
   */
  abstract protected function getLegacyBudgetFieldName(): string;

  /**
   * Returns the new budget field name.
   *
   * @return string
   *   The budget field.
   */
  abstract protected function getBudgetFieldName(): string;

}
