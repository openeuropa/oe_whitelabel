<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Language\LanguageInterface;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\media\Entity\Media;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Drupal\oe_bootstrap_theme\ValueObject\ImageValueObject;
use Drupal\oe_content_featured_media_field\Plugin\Field\FieldType\FeaturedMediaItem;

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
   * {@inheritdoc}
   */
  function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];
    $parent_elements = parent::viewElements($items, $langcode);
    if(empty($parent_elements)) {
      return $elements;
    }

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

      $thumbnail = $media->get('thumbnail')->first();
      if($parent_element['#image_style']) {
        $element = ImageValueObject::fromStyledImageItem($thumbnail, $parent_element['#image_style']);
      }
      else {
        $element = ImageValueObject::fromImageItem($thumbnail);
      }
      $elements = [
        'src' => $element->getSource(),
        'alt' => $element->getAlt()
      ];
      if (!empty($elements['src'])) {
        // Waited output is 1 dimensional array, so we will return only the first element.
        return $elements;
      }
    }

    return $elements;
  }

  /**
   * Load entities if not unsaved (TRUE in major cases).
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * No needs to check renderable elements.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    return $this->viewElements($items, $langcode);
  }

}
