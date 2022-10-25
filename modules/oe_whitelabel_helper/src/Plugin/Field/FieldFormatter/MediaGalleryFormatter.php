<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media gallery field formatter.
 *
 * The formatter renders a media field using the gallery pattern.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_gallery",
 *   label = @Translation("Gallery"),
 *   description = @Translation("Display media entities using the gallery pattern."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaGalleryFormatter extends EntityReferenceFormatterBase {

  /**
   * A list of field mappings to gallery item rows properties, keyed by bundle.
   *
   * @var array
   */
  protected array $mappings = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a MediaGalleryFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $view_builder = $this->entityTypeManager->getViewBuilder('media');

    $cacheable_metadata = new CacheableMetadata();
    $gallery_items = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $cacheable_metadata->addCacheableDependency($entity);

      foreach ($this->getFieldMappings($entity->bundle()) as $pattern_field => $entity_field) {
        $gallery_items[$delta][$pattern_field] = $view_builder->viewField($entity->get($entity_field), 'oe_w_pattern_gallery_item');
      }
    }

    $cacheable_metadata->applyTo($elements);

    if (!empty($gallery_items)) {
      $elements[] = [
        '#type' => 'pattern',
        '#id' => 'gallery',
        '#items' => $gallery_items,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for fields referencing media entities.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media';
  }

  /**
   * Returns the media bundle fields mapped to gallery item properties.
   *
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return array
   *   An associative array of gallery item property and field names.
   */
  protected function getFieldMappings(string $bundle_id): array {
    if (!isset($this->mappings[$bundle_id])) {
      $this->mappings[$bundle_id] = [];

      $display_manager = $this->entityTypeManager->getStorage('entity_view_display');
      $entity_view_displays = $display_manager->loadByProperties([
        'targetEntityType' => 'media',
        'bundle' => $bundle_id,
        'mode' => 'oe_w_pattern_gallery_item',
      ]);

      if (empty($entity_view_displays)) {
        return [];
      }

      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
      $entity_view_display = reset($entity_view_displays);
      foreach ($entity_view_display->getComponents() as $field_name => $settings) {
        $plugin = $entity_view_display->getRenderer($field_name);
        $property = $plugin->getThirdPartySetting('oe_whitelabel_helper', 'gallery_formatter');

        // If no value is specified or if the property has been already mapped,
        // skip this entry.
        if ($property === NULL || array_key_exists($property, $this->mappings[$bundle_id])) {
          continue;
        }

        $this->mappings[$bundle_id][$property] = $field_name;
      }
    }

    return $this->mappings[$bundle_id];
  }

}
