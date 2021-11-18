<?php

namespace Drupal\oe_whitelabel_helper\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\field\FieldConfigInterface;
use Drupal\field_group\FieldGroupFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'multicolumn' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "multicolumn",
 *   label = @Translation("Multicolumn formatter"),
 *   description = @Translation("Breaks fields evenly into two columns."),
 *   supported_contexts = {
 *     "view",
 *   }
 * )
 */
class MultiColumn extends FieldGroupFormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Render API properties.
   *
   * @var array
   */
  protected $renderApiProperties = [
    '#theme',
    '#markup',
    '#prefix',
    '#suffix',
    '#type',
    'widget',
  ];

  /**
   * Constructs a Popup object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param object $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $group, $settings, $label);
    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['group'],
      $configuration['settings'],
      $configuration['label'],
      $container->get('module_handler'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'first_column' => '',
      'second_column' => '',
      'hide_table_if_empty' => FALSE,
    ] + parent::defaultSettings($context);

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['first_column'] = [
      '#title' => $this->t('First column classes'),
      '#description' => $this->t('Add custom classes to the first column.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('first_column'),
    ];
    $form['second_column'] = [
      '#title' => $this->t('Second column classes'),
      '#description' => $this->t('Add custom classes to the second column.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('second_column'),
    ];

    $form['hide_table_if_empty'] = [
      '#title' => $this->t('Hide the table if empty'),
      '#description' => $this->t('Do not output any table or container markup if there are no rows with values.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_table_if_empty'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display results as a 2 column table.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $element['#mode'] = $this->context;
    // Allow modules to alter the rows, useful for removing empty rows.
    $children = Element::children($element, TRUE);
    $this->moduleHandler->alter('field_group_table_rows', $element, $children);

    if ($this->getSetting('hide_table_if_empty')) {
      field_group_remove_empty_display_groups($element, []);
      if ($element == []) {
        return;
      }
    }

    // Elements wrapper.
    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'my-3';

    // Print the fieldset title.
    $element['fieldset_title']['#type'] = 'markup';
    $element['fieldset_title']['#markup'] = '<h4 class="fw-bold mb-4">' . $this->group->label . '</h4>';

    // Fieldset data container.
    $element['fieldset']['data']['#type'] = 'container';
    $element['fieldset']['data']['#attributes']['class'][] = 'row';

    // Set additional classes per column.
    $classes = ['col-12', 'col-md-6'];
    if ($this->getSetting('first_column')) {
      $first_column_classes = array_merge($classes, [$this->getSetting('first_column')]);
    }

    if ($this->getSetting('second_column')) {
      $second_column_classes = array_merge($classes, [$this->getSetting('second_column')]);
    }
    // Columns container.
    $element['fieldset']['data']['first_column']['#type'] = 'container';
    $element['fieldset']['data']['first_column']['#attributes'] = [
      'class' => $this->getSetting('first_column') ? $first_column_classes : $classes,
    ];
    $element['fieldset']['data']['second_column']['#type'] = 'container';
    $element['fieldset']['data']['second_column']['#attributes'] = [
      'class' => $this->getSetting('second_column') ? $second_column_classes : $classes,
    ];

    // Number of items in the first columns.
    $first_column_elements = array_slice($children, 0, ceil(count($children) / 2));

    // Build fieldset column(s).
    foreach ($children as $key => $field_name) {
      $column = in_array($field_name, $first_column_elements) ? 'first_column' : 'second_column';
      if ($row = $this->buildRow($element, $field_name)) {
        $element['fieldset']['data'][$column][$field_name] = $row;
      }
      unset($element[$field_name]);
    }
  }

  /**
   * Build the row for requested element.
   *
   * @param array $element
   *   Rendering array of an element.
   * @param string $field_name
   *   The name of currently handling field.
   *
   * @return array
   *   Table row definition on success or an empty array otherwise.
   */
  protected function buildRow(array $element, $field_name) {
    $item = $this->getRowItem($element, $field_name);
    $build = [];

    if (!$item) {
      return $build;
    }
    $item['#attributes'] = ['class' => 'col-12 col-md-6'];
    $build = $item;

    return $build;
  }

  /**
   * Return item definition array.
   *
   * @param array $element
   *   Rendering array.
   * @param string $field_name
   *   Item field machine name.
   *
   * @return array
   *   Item definition array on success or empty array otherwise.
   */
  protected function getRowItem(array $element, $field_name) {
    $item = isset($element[$field_name]) ? $element[$field_name] : [];
    $is_empty = !is_array($item) || !array_intersect($this->renderApiProperties, array_keys($item));

    if ($is_empty && $this->getSetting('always_show_field_value') && isset($element['#entity_type'], $element['#bundle'])) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($element['#entity_type'], $element['#bundle']);
      $field_definition = isset($field_definitions[$field_name]) ? $field_definitions[$field_name] : NULL;

      if ($field_definition instanceof FieldConfigInterface) {
        $is_empty = FALSE;

        $item = [
          '#title' => $field_definition->label(),
          '#label_display' => 'above',
          '#markup' => Xss::filter($this->getSetting('empty_field_placeholder')),
        ];
      }
    }

    $item['#attributes']['class'] = ['row'];
    return $is_empty ? [] : $item;
  }

}
