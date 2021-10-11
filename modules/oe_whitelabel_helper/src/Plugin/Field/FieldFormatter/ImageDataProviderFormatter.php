<?php

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "image_data_provider",
 *   label = @Translation("Image data provider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageDataProviderFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // @TODO: Add a relevant summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    // @TODO: Add caching.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    if (empty($images = $this->getEntitiesToView($items, $langcode))) {
      // Early opt-out if the field is empty.
      return $element;
    }

    $image = $images[0];
    $image_uri = $image->getFileUri();
    $url = file_create_url($image_uri);
    // @TODO: Add image style in config form.
    $url = file_url_transform_relative($url);
    $element = [
      '#fields' => [
        'path' => $url,
        'alt' => $items->getValue()[0]['alt'],
      ],
    ];

    return $element;
  }
}
