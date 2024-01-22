<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Traits;

use Drupal\node\NodeInterface;

/**
 * Contains methods to create node entities for testing.
 *
 * When adding a method to create a specific bundle, the method name MUST follow
 * the naming: "create" + bundle name in camel case + "Node".
 */
trait NodeCreationTrait {

  use MediaCreationTrait;

  /**
   * Creates a news node.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function createNewsNode(array $values = []): NodeInterface {
    $values['type'] = 'oe_sc_news';
    $values = $this->fillMediaFieldsIfEmpty([
      'oe_featured_media' => 'image',
    ], $values);

    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values + [
        'title' => 'Test news node',
        'oe_summary' => 'News summary.',
        'body' => 'News body',
        'oe_publication_date' => [
          'value' => '2022-02-09',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    /** @var \Drupal\node\NodeInterface $node */
    $node->save();

    return $node;
  }

  /**
   * Creates an event node.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function createEventNode(array $values = []): NodeInterface {
    $values['type'] = 'oe_sc_event';
    $values = $this->fillMediaFieldsIfEmpty([
      'oe_documents' => 'document',
      'oe_featured_media' => 'image',
    ], $values);

    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values + [
        'title' => 'Test event node',
        'oe_summary' => 'Event summary.',
        'body' => 'Event body',
        'oe_sc_event_dates' => [
          'value' => '2022-02-09T20:00:00',
          'end_value' => '2022-02-09T22:00:00',
        ],
        'oe_sc_event_location' => [
          'country_code' => 'BE',
          'address_line1' => 'Charlemagne building, Wetstraat 170',
          'postal_code' => '1040',
          'locality' => 'Brussel',
        ],
        'oe_sc_event_registration_url' => 'https://europa.eu',
        'uid' => 1,
        'status' => 1,
      ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a person node.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function createPersonNode(array $values = []): NodeInterface {
    $values['type'] = 'oe_sc_person';
    $values = $this->fillMediaFieldsIfEmpty([
      'oe_sc_person_image' => 'image',
    ], $values);

    if (!array_key_exists('oe_sc_person_documents', $values)) {
      $document = $this->createDocumentMedia();

      $document_reference_storage = \Drupal::entityTypeManager()->getStorage('oe_document_reference');
      $document_reference = $document_reference_storage
        ->create([
          'type' => 'oe_document',
          'oe_document' => $document,
          'status' => 1,
        ]);
      $document_reference->save();
      $document_group_reference = $document_reference_storage
        ->create([
          'type' => 'oe_document_group',
          'oe_title' => 'Curriculum Vitae',
          'oe_documents' => [$document, $document],
          'status' => 1,
        ]);
      $document_reference->save();

      $values['oe_sc_person_documents'] = [$document_reference, $document_group_reference];
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values + [
        'oe_sc_person_first_name' => 'Stefan',
        'oe_sc_person_last_name' => 'Mayer',
        'oe_sc_person_country' => 'DE',
        'oe_sc_person_occupation' => 'DG Test',
        'oe_sc_person_position' => 'Director',
        'oe_summary' => 'This field is used to add a short biography of the person.',
        'oe_sc_person_additional_info' => 'Additional information example field.',
        'oe_social_media_links' => [
          'uri' => 'https://twitter.com',
          'title' => 'Twitter profile',
          'link_type' => 'twitter',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a publication node.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function createPublicationNode(array $values = []): NodeInterface {
    $values['type'] = 'oe_sc_publication';

    $values = $this->fillMediaFieldsIfEmpty([
      'oe_featured_media' => 'image',
    ], $values);

    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values + [
        'title' => 'Publication test',
        'oe_summary' => 'This is an example summary.',
        'oe_publication_date' => '2022-08-02',
        'uid' => 1,
        'status' => 1,
      ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a project node.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function createProjectNode(array $values = []): NodeInterface {
    $values['type'] = 'oe_project';

    $values = $this->fillMediaFieldsIfEmpty([
      'oe_featured_media' => 'image',
    ], $values);

    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values + [
        'title' => 'Project test',
        'oe_subject' => 'http://data.europa.eu/uxp/1005',
        'oe_teaser' => 'Project teaser text',
        'oe_project_dates' => [
          'value' => '2020-05-10',
          'end_value' => '2025-05-15',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->save();

    return $node;
  }

  /**
   * Helper method to fill media fields with a value when none is present.
   *
   * This prevents creating extra media entities in the test.
   *
   * @param array $fields
   *   A list of field names as keys and the corresponding media type as value.
   * @param array $values
   *   The current field values.
   *
   * @return array
   *   The values with defaults added.
   */
  protected function fillMediaFieldsIfEmpty(array $fields, array $values): array {
    foreach ($fields as $name => $media_type) {
      if (!array_key_exists($name, $values)) {
        $values[$name] = [$this->createMediaByBundle($media_type)];
      }
    }

    return $values;
  }

}
