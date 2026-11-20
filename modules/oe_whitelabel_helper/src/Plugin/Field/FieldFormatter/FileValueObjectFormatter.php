<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\oe_bootstrap_theme\ValueObject\FileValueObject;

/**
 * Format a File into object {'src', 'alt'}.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_file_objectvalue",
 *   label = @Translation("File value object"),
 *   field_types = {
 *     "file",
 *   },
 * )
 */
class FileValueObjectFormatter extends FileFormatterBase {

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\media\Entity\Media[] $medias */
    $files = $this->getEntitiesToView($items, $langcode);
    if (empty($files)) {
      return $elements;
    }

    /** @var \Drupal\file\Entity\File $file_entity */
    foreach ($files as $file_entity) {
      $object = FileValueObject::fromFileEntity($file_entity);
      return $object->getArray();
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
