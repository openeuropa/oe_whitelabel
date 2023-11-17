<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;

/**
 * Contains methods to create media entities for testing.
 *
 * When adding a method to create a specific bundle, the method name MUST follow
 * the naming: "create" + bundle name in camel case + "Media".
 */
trait MediaCreationTrait {

  /**
   * Create a remote video media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createRemoteVideoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'remote_video';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_oembed_video' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
    ]);
  }

  /**
   * Create an AV Portal photo media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createAvPortalPhotoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'av_portal_photo';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_avportal_photo' => 'P-038924/00-15',
    ]);
  }

  /**
   * Create an AV Portal video media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createAvPortalVideoMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'av_portal_video';
    // Title is fetched automatically from remote, so it must stay empty.
    $values['name'] = NULL;

    return $this->createMedia($values + [
      'oe_media_avportal_video' => 'I-163162',
    ]);
  }

  /**
   * Create a document media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createDocumentMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'document';

    // If no file has been passed and the document type is local or not defined,
    // we create a sample pdf file and create a local document.
    if (!isset($values['oe_media_file']['target_id']) &&
      (!isset($values['oe_media_file_type']) || $values['oe_media_file_type'] === 'local')
    ) {
      $pdf = File::create([
        'uri' => \Drupal::service('file_system')->copy(
          \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
          'public://sample.pdf'
        ),
      ]);
      $pdf->save();

      $values['oe_media_file_type'] = 'local';
      $values['oe_media_file']['target_id'] = $pdf->id();
    }

    return $this->createMedia($values + [
      'name' => 'Document title',
    ]);
  }

  /**
   * Create an image media with default values, ready to use in tests.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createImageMedia(array $values = []): MediaInterface {
    $values['bundle'] = 'image';

    if (!isset($values['oe_media_image']['target_id'])) {
      $image = File::create([
        'uri' => \Drupal::service('file_system')->copy(
          \Drupal::service('extension.list.theme')->getPath('oe_whitelabel') . '/tests/fixtures/example_1.jpeg',
          'public://example_1.jpeg'
        ),
      ]);
      $image->save();

      $values = NestedArray::mergeDeep([
        'oe_media_image' => [
          'target_id' => $image->id(),
          'alt' => 'Alt text',
        ],
      ], $values);
    }

    return $this->createMedia($values + [
      'name' => 'Image title',
    ]);
  }

  /**
   * Creates a media entity.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The created entity.
   */
  protected function createMedia(array $values = []): MediaInterface {
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a media of a specific bundle ready to use in tests.
   *
   * @param string $bundle
   *   The bundle of the media.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function createMediaByBundle(string $bundle, array $values = []): MediaInterface {
    $callable = [
      static::class,
      'create' . strtr(ucwords($bundle, '_'), ['_' => '']) . 'Media',
    ];

    if (!is_callable($callable)) {
      throw new \Exception(sprintf('No methods found to create medias of bundle "%s".', $bundle));
    }

    /** @var \Drupal\media\MediaInterface $media */
    $media = call_user_func($callable, $values);

    return $media;
  }

}
