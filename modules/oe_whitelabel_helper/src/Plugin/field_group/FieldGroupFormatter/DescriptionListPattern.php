<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\ui_patterns\UiPatternsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Format a field group using the description list pattern.
 *
 * @FieldGroupFormatter(
 *   id = "oe_whitelabel_helper_description_list_pattern",
 *   label = @Translation("Description list pattern"),
 *   description = @Translation("Format a field group using the description list pattern."),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
class DescriptionListPattern extends PatternFormatterBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a DescriptionList object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\ui_patterns\UiPatternsManager $patterns_manager
   *   The pattern manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, UiPatternsManager $patterns_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $patterns_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('plugin.manager.ui_patterns')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getPatternId(): string {
    return 'description_list';
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    // Only support horizontal mode in this field group formatter.
    $element['pattern']['#settings']['orientation'] = 'horizontal';
  }

  /**
   * {@inheritdoc}
   */
  protected function getFields(array &$element, $rendering_object): ?array {
    $fields = [];

    foreach (Element::children($element) as $field_name) {
      $field_element = $element[$field_name];
      $field_element['#label_display'] = 'hidden';
      $field_markup = $this->renderer->render($field_element);
      if (trim((string) $field_markup) === '') {
        continue;
      }
      // Assign field label and content to the pattern's fields.
      $fields['items'][] = [
        'term' => $field_element['#title'] ?? '',
        'definition' => $field_markup,
      ];
    }

    if (empty($fields['items'])) {
      return NULL;
    }

    return $fields;
  }

}
