<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\oe_whitelabel\PatternAssertions\CardAssert;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the rendering of the teaser view mode of Project content type.
 */
class ProjectRenderTest extends ContentRenderTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'composite_reference',
    'datetime',
    'datetime_range',
    'entity_reference_revisions',
    'field_group',
    'file',
    'image',
    'inline_entity_form',
    'link',
    'maxlength',
    'media',
    'node',
    'oe_content',
    'oe_content_extra',
    'oe_content_extra_project',
    'oe_content_documents_field',
    'oe_content_entity',
    'oe_content_extra_project',
    'oe_content_featured_media_field',
    'oe_content_project',
    'oe_media',
    'options',
    'rdf_skos',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('oe_content_entity');
    $this->installEntitySchema('oe_contact');
    $this->installEntitySchema('oe_organisation');

    module_load_include('install', 'oe_content_documents_field');
    oe_content_documents_field_install(FALSE);

    module_load_include('install', 'oe_whitelabel_extra_project');
    oe_whitelabel_extra_project_install(FALSE);

    $this->installConfig([
      'media',
      'node',
      'oe_content',
      'oe_content_entity',
      'oe_content_documents_field',
      'oe_content_featured_media_field',
      'oe_content_project',
      'oe_content_extra_project',
    ]);

    module_load_include('install', 'oe_content');
    oe_content_install(FALSE);
  }

  /**
   * Test a project being rendered as a teaser.
   */
  public function testProjectTeaser(): void {
    $file = file_save_data(file_get_contents(drupal_get_path('theme', 'oe_whitelabel') . '/tests/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'test image',
      'oe_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Alternative text',
        'caption' => 'Caption',
      ],
    ]);
    $media->save();

    $coordinator = Organisation::create([
      'name' => 'Coordinator 1',
      'bundle' => 'oe_stakeholder',
    ]);
    $coordinator->set('oe_address', [
      'country_code' => 'BE',
      'locality' => 'Brussels',
      'postal_code' => 1000,
      'address_line1' => 'The street',
    ]);
    $coordinator->save();

    $participant = Organisation::create([
      'name' => 'Participant 1',
      'bundle' => 'oe_stakeholder',
    ]);
    $participant->set('oe_address', [
      'country_code' => 'BE',
      'locality' => 'Brussels',
      'postal_code' => 1000,
      'address_line1' => 'The street',
    ]);
    $participant->save();

    $values = [
      'type' => 'oe_project',
      'title' => 'Project 1',
      'oe_subject' => 'http://data.europa.eu/uxp/1005',
      'oe_author' => 'http://publications.europa.eu/resource/authority/corporate-body/ACJHR',
      'oe_content_owner' => 'http://publications.europa.eu/resource/authority/corporate-body/ABEC',
      'oe_project_locations' => [
        [
          'country_code' => 'BE',
          'locality' => 'Brussels',
          'postal_code' => 1000,
        ],
      ],
      'body' => 'The body text',
      'oe_teaser' => 'The teaser text',
      'oe_project_coordinators' => [
        [
          'target_id' => $coordinator->id(),
          'target_revision_id' => $coordinator->getRevisionId(),
        ],
      ],
      'oe_project_participants' => [
        [
          'target_id' => $participant->id(),
          'target_revision_id' => $participant->getRevisionId(),
        ],
      ],
      'oe_featured_media' => [
        [
          'target_id' => $media->id(),
          'caption' => 'Caption text',
          'alt' => 'Alternative text',
        ],
      ],
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2025-05-15',
      ],
      'status' => 1,
    ];

    $node = Node::create($values);
    $node->save();

    $build = $this->nodeViewBuilder->view($node, 'teaser');
    $html = $this->renderRoot($build);

    $assert = new CardAssert();

    $expected_values = [
      'title' => 'Project 1',
      'url' => '/node/1',
      'description' => 'The teaser text',
      'badges' => ['EU financing'],
      'image' => [
        'src' => 'example_1.jpeg',
        'alt' => 'Alternative text',
      ],
      'content' => [
        '2020',
        '2025',
      ],
    ];
    $assert->assertPattern($expected_values, $html);
    $assert->assertVariant('search', $html);
  }

}
