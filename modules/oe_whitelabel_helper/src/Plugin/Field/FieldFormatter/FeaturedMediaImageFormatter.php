<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\media\Entity\Media;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Drupal\oe_bootstrap_theme\ValueObject\ImageValueObject;
use Drupal\oe_content_featured_media_field\Plugin\Field\FieldType\FeaturedMediaItem;

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

    foreach ($parent_elements as $delta => $parent_element) {

      /** @var FeaturedMediaItem $item */
      $item = $items[$delta];

      /** @var \Drupal\media\Entity\Media $media */
      $media = Media::load($item->get('target_id')->getValue());
      // Retrieve the correct media translation.
      /** @var \Drupal\media\Entity\Media $media */
      $media = $this->entityRepository->getTranslationFromContext($media, $langcode);

      // Get the media source.
      $source = $media->getSource();
      $is_image = $source instanceof MediaAvPortalPhotoSource || $source instanceof Image;
      // If it's not an image bail out.
      if (!$is_image) {
        continue;
      }

      if($parent_element['#image_style']) {
        $thumbnail = $media->get('thumbnail')->first();
        $elements[$delta]['featured_media'] = ['content' => ImageValueObject::fromStyledImageItem($thumbnail, $parent_element['#image_style'])->toRenderArray()];
      }
      else {
        $thumbnail = $media->get('thumbnail')->first();
        $elements[$delta]['featured_media'] = ['content' => ImageValueObject::fromImageItem($thumbnail)->toRenderArray()];
      }
      // Add the caption as the next element after the media link.
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
   *
   * One step back to have both image and file ER plugins extend this, because
   * EntityReferenceItem::isDisplayed() doesn't exist, except for ImageItem
   * which is always TRUE anyway for type image and file ER.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

}
