<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\ui_patterns\UiPatternsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Format a field group using the inpage navigation pattern.
 *
 * @FieldGroupFormatter(
 *   id = "oe_whitelabel_helper_inpage_navigation_pattern",
 *   label = @Translation("Inpage navigation pattern"),
 *   description = @Translation("Format a field group using the inpage navigation pattern."),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
class InpageNavigationPattern extends PatternFormatterBase {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs an InpageNavigationPattern object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\UiPatternsManager $patterns_manager
   *   The pattern manager.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, UiPatternsManager $patterns_manager, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $patterns_manager);
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getPatternId(): string {
    return 'inpage_navigation';
  }

  /**
   * {@inheritdoc}
   */
  protected function getFields(array &$element, $rendering_object): ?array {
    $fields = [];
    $visible_displays = ['above', 'inline'];

    foreach (Element::children($element) as $delta => $field_name) {
      $field_element = $element[$field_name];
      $fields['content'][$delta] = $field_element;

      if (!array_key_exists('#title', $field_element)) {
        continue;
      }

      // If the label is not configured for display, do not add this field to
      // the navigation entries.
      if (!in_array($field_element['#label_display'] ?? NULL, $visible_displays)) {
        continue;
      }

      $field_label = $field_element['#title'];
      $id = $field_element['#title_attributes']['id'] ?? NULL;
      // Generate an ID for the title, if one doesn't exist yet.
      if ($id === NULL) {
        // Transliterate labels before generating ASCII-only HTML IDs.
        $transliterated_label = $this->transliteration->transliterate($field_label);
        // Prefix IDs to avoid collisions with existing page elements.
        $id = Html::getUniqueId(Html::cleanCssIdentifier('inpage-navigation-' . $transliterated_label));
        $fields['content'][$delta]['#title_attributes']['id'] = $id;
      }

      $fields['links'][] = [
        'label' => $field_label,
        'path' => '#' . $id,
      ];
    }

    // Use the field group label as title.
    if (!empty($fields['links'])) {
      $fields['title'] = $this->getLabel();
    }

    return $fields;
  }

}
