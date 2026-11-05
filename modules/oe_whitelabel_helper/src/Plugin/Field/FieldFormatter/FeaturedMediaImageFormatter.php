<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\FileInterface;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\media\Entity\Media;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Drupal\oe_bootstrap_theme\ValueObject\ImageValueObject;

/**
 * Plugin implementation of the 'Featured media as label' formatter.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_oefeaturedmedia_image",
 *   label = @Translation("Image"),
 *   description = @Translation("Display the referenced media entity as Image."),
 *   field_types = {
 *     "oe_featured_media"
 *   }
 * )
 */
class FeaturedMediaImageFormatter extends FeaturedMediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $parent_elements = parent::viewElements($items, $langcode);
    $display_caption_setting = $this->getSetting('display_caption');

    foreach ($parent_elements as $delta => $parent_element) {

      /** @var \Drupal\oe_content_featured_media_field\Plugin\Field\FieldType\FeaturedMediaItem $item */
      $item = $items[$delta];

      /** @var \Drupal\media\Entity\Media|null $media */
      $media = Media::load($item->get('target_id')->getValue());
      if (!$media) {
        continue;
      }

      // Retrieve the correct media translation.
      $media = $this->entityRepository->getTranslationFromContext($media, $langcode);

      // Get the media source.
      $source = $media->getSource();
      $is_image = $source instanceof MediaAvPortalPhotoSource || $source instanceof Image;
      if (!$is_image) {
        continue;
      }

      // Ensure thumbnail exists.
      if (!$media->hasField('thumbnail') || $media->get('thumbnail')->isEmpty()) {
        continue;
      }

      $thumbnail = $media->get('thumbnail')->first();
      if (!$thumbnail) {
        continue;
      }

      // Load the file entity.
      /** @var \Drupal\file\FileInterface|null $file_entity */
      $file_entity = $thumbnail->entity ?? NULL;
      if (!$file_entity instanceof FileInterface) {
        continue;
      }

      // Build render array using image style or not.
      if (!empty($parent_element['#image_style']) && $file_entity->getMimeType() !== 'image/svg+xml') {
        $image_object = ImageValueObject::fromStyledImageItem($thumbnail, $parent_element['#image_style']);
      }
      else {
        $image_object = ImageValueObject::fromImageItem($thumbnail);
      }

      $elements[$delta]['featured_media'] = [
        'content' => $image_object->toRenderArray(),
      ];

      // Add caption if enabled.
      if (!empty($display_caption_setting)) {
        $elements[$delta]['featured_media']['caption'] = [
          '#plain_text' => $item->caption,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

}
