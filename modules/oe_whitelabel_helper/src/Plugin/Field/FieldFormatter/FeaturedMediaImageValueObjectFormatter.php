<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Featured media as label' formatter.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_oefeaturedmedia_imageobjectvalue",
 *   label = @Translation("ImageValueObject for pattern"),
 *   description = @Translation("Return an object {path.alt,position} keys."),
 *   field_types = {
 *     "oe_featured_media"
 *   }
 * )
 */
class FeaturedMediaImageValueObjectFormatter extends FeaturedMediaFormatterBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    $parent_elements = parent::viewElements($items, $langcode);
    if (empty($parent_elements)) {
      return $elements;
    }

    foreach ($parent_elements as $delta => $parent_element) {
      /** @var \Drupal\oe_content_featured_media_field\Plugin\Field\FieldType\FeaturedMediaItem $item */
      $item = $items[$delta];

      $target_id = $item->get('target_id')->getValue();
      if (empty($target_id)) {
        continue;
      }

      /** @var \Drupal\media\Entity\Media|null $media */
      $media = Media::load($target_id);
      if (!$media) {
        continue;
      }

      // Get the proper translation.
      $media = $this->entityRepository->getTranslationFromContext($media, $langcode);

      // Check if the media source is image-based.
      $source = $media->getSource();
      if (!($source instanceof MediaAvPortalPhotoSource || $source instanceof Image)) {
        continue;
      }

      // Ensure thumbnail field exists and is not empty.
      if (!$media->hasField('thumbnail') || $media->get('thumbnail')->isEmpty()) {
        continue;
      }

      $thumbnail = $media->get('thumbnail')->first();
      if (!$thumbnail) {
        continue;
      }

      /** @var \Drupal\file\FileInterface|null $file_entity */
      $file_entity = $thumbnail->entity ?? NULL;
      if (!$file_entity instanceof FileInterface) {
        continue;
      }

      $mime_type = $file_entity->getMimeType();
      $is_svg = $mime_type === 'image/svg+xml';

      // Try to load the image style (if provided).
      $image_style = NULL;
      if (!empty($parent_element['#image_style'])) {
        $image_style = $this->entityTypeManager->getStorage('image_style')->load($parent_element['#image_style']);
      }

      // Generate image src URL.
      if ($image_style && !$is_svg) {
        $src = $this->fileUrlGenerator->transformRelative($image_style->buildUrl($file_entity->getFileUri()));
      }
      else {
        $src = $this->fileUrlGenerator->generateString($file_entity->getFileUri());
      }

      $elements = [
        'src' => $src,
        'alt' => $thumbnail->get('alt')->getValue() ?? '',
      ];

      // Only return the first valid image.
      if (!empty($elements['src'])) {
        return $elements;
      }
    }

    return $elements;
  }

  /**
   * Avoid loading unsaved entities.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * No need to check renderable elements.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    return $this->viewElements($items, $langcode);
  }

}
