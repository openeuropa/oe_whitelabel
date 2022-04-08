<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\MediaInterface;
use Drupal\oe_bootstrap_theme\ValueObject\FileValueObject;

/**
 * Wraps a media entity of bundle "document".
 *
 * @internal
 */
class DocumentMediaWrapper {

  /**
   * The media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $media;

  /**
   * Construct a new wrapper object.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media to wrap.
   */
  public function __construct(MediaInterface $media) {
    if ($media->bundle() !== 'document') {
      throw new \InvalidArgumentException(sprintf('Invalid media of type "%s" passed, "document" expected.', $media->bundle()));
    }

    $this->media = $media;
  }

  /**
   * Returns if the media is empty.
   *
   * A media is considered empty if the current active field, based on the type,
   * is empty.
   *
   * @return bool
   *   Whether the media is referencing a field or not.
   */
  public function isEmpty(): bool {
    $field = $this->getActiveField();
    return !$field || $field->isEmpty();
  }

  /**
   * Returns the type of the media.
   *
   * @return string|null
   *   The media type, usually "remote" or "local". NULL if no value set.
   */
  public function getType(): ?string {
    return $this->media->get('oe_media_file_type')->value;
  }

  /**
   * Creates a file value object from the current media values.
   *
   * @return \Drupal\oe_bootstrap_theme\ValueObject\FileValueObject|null
   *   A file value object, or NULL if the media is empty.
   */
  public function toFileValueObject(): ?FileValueObject {
    if ($this->isEmpty()) {
      return NULL;
    }

    $field = $this->getActiveField();
    $object = $this->getType() === 'remote'
      ? FileValueObject::fromFileLink($field->first())
      : FileValueObject::fromFileEntity($field->first()->entity);

    return $object->setTitle($this->media->getName())
      ->setLanguageCode($this->media->language()->getId());
  }

  /**
   * Returns the field that is being used for the document, based on the type.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The field item list, or NULL if an invalid type is specified.
   */
  protected function getActiveField(): ?FieldItemListInterface {
    if (!$this->getType()) {
      return NULL;
    }

    switch ($this->getType()) {
      case 'remote':
        return $this->media->get('oe_media_remote_file');

      case 'local':
        return $this->media->get('oe_media_file');

      default:
        return NULL;
    }
  }

}
