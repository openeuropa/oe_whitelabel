<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Featured media as label' formatter.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_entityreference_imageobjectvalue",
 *   label = @Translation("ImageValueObject for pattern"),
 *   description = @Translation("Return an object {src,alt}."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceImageValueObjectFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The Get EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param array $parent_params
   *   Array of parent parameters (PHPMD is blocking when 10 parameters).
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(array $parent_params, FileUrlGeneratorInterface $file_url_generator = NULL, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($parent_params['plugin_id'], $parent_params['plugin_definition'], $parent_params['field_definition'], $parent_params['settings'], $parent_params['label'], $parent_params['view_mode'], $parent_params['third_party_settings']);

    $this->fileUrlGenerator = $file_url_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $parent_params = [
      'plugin_id' => $plugin_id,
      'plugin_definition' => $plugin_definition,
      'field_definition' => $configuration['field_definition'],
      'settings' => $configuration['settings'],
      'label' => $configuration['label'],
      'view_mode' => $configuration['view_mode'],
      'third_party_settings' => $configuration['third_party_settings'],
    ];
    return new static(
      $parent_params,
      $container->get('file_url_generator'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    return [
      'image_style' => [
        '#title' => $this->t('Image style'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('image_style'),
        '#empty_option' => $this->t('None (original image)'),
        '#options' => $image_styles,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('URL for Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      'src' => '',
      'alt' => '',
    ];

    /** @var \Drupal\media\Entity\Media[] $media_entities */
    $media_entities = $this->getEntitiesToView($items, $langcode);
    if (empty($media_entities)) {
      return $elements;
    }

    $image_style_setting = $this->getSetting('image_style');
    $image_style = NULL;
    $cache_tags = [];

    if (!empty($image_style_setting)) {
      $image_style = $this->entityTypeManager->getStorage('image_style')->load($image_style_setting);
      if (!empty($image_style)) {
        $cache_tags = $image_style->getCacheTags();
      }
    }

    $elements['#cache'] = array_merge($elements['#cache'] ?? [], $cache_tags);

    foreach ($media_entities as $media) {
      // Dynamically find a file or image field in the media entity.
      $field_name = NULL;
      foreach ($media->getFieldDefinitions() as $field_definition) {
        if (in_array($field_definition->getType(), ['file', 'image'], TRUE)) {
          $field_name = $field_definition->getName();
          break;
        }
      }

      if (!$field_name || !$media->hasField($field_name)) {
        continue;
      }

      $file_items = $media->get($field_name)->getValue();
      foreach ($file_items as $file_item) {
        if (empty($file_item['target_id'])) {
          continue;
        }

        /** @var \Drupal\file\Entity\File $file_entity */
        $file_entity = $this->entityTypeManager->getStorage('file')->load($file_item['target_id']);
        if (!$file_entity) {
          continue;
        }

        $file_uri = $file_entity->getFileUri();
        $alt_text = $file_item['alt'] ?? '';

        if (!empty($image_style)) {
          $elements['src'] = $this->fileUrlGenerator->transformRelative($image_style->buildUrl($file_uri));
        }
        else {
          $elements['src'] = $this->fileUrlGenerator->generateString($file_uri);
        }

        $elements['alt'] = $alt_text;

        // Add file cacheability.
        CacheableMetadata::createFromObject($file_entity)->addCacheableDependency($elements);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Only manage Media.
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media');
  }

  /**
   * {@inheritdoc}
   *
   * One step back to have both image and file ER plugins extend this, because
   * EntityReferenceItem::isDisplayed() doesn't exist, except for ImageItem
   * which is always TRUE anyway for type image and file ER.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * No needs to check renderable elements.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }

    return $this->viewElements($items, $langcode);
  }

}
