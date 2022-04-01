<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Base class for testing the content being rendered.
 */
abstract class ContentRenderTestBase extends AbstractKernelTestBase {

  use SparqlConnectionTrait;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $nodeViewBuilder;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'composite_reference',
    'datetime',
    'entity_browser',
    'entity_reference',
    'entity_reference_revisions',
    'field',
    'field_group',
    'file',
    'file_link',
    'filter',
    'inline_entity_form',
    'link',
    'media',
    'media_avportal',
    'media_avportal_mock',
    'node',
    'oe_content',
    'oe_content_departments_field',
    'oe_content_documents_field',
    'oe_content_entity',
    'oe_content_entity_contact',
    'oe_content_entity_organisation',
    'oe_content_extra',
    'oe_content_extra_project',
    'oe_content_featured_media_field',
    'oe_content_project',
    'oe_content_reference_code_field',
    'oe_media',
    'oe_media_avportal',
    'options',
    'rdf_skos',
    'sparql_entity_storage',
    'text',
    'typed_link',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpSparql();

    $this->installEntitySchema('node');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');

    $this->installConfig([
      'file',
      'field',
      'entity_reference',
      'entity_reference_revisions',
      'composite_reference',
      'node',
      'media',
      'filter',
      'oe_media',
      'media_avportal',
      'oe_media_avportal',
      'typed_link',
      'address',
    ]);

    // Importing of configs which related to media av_portal output.
    $this->container->get('config.installer')->installDefaultConfig('theme', 'oe_whitelabel');

    $this->container->get('module_handler')->loadInclude('oe_content_documents_field', 'install');
    oe_content_documents_field_install(FALSE);

    $this->installEntitySchema('oe_contact');
    $this->installEntitySchema('oe_organisation');

    $this->installConfig([
      'oe_content',
      'oe_content_entity',
      'oe_content_entity_contact',
      'oe_content_entity_organisation',
      'oe_content_departments_field',
      'oe_content_reference_code_field',
      'oe_content_featured_media_field',
      'oe_content_project',
      'oe_content_extra',
      'oe_content_extra_project',
    ]);

    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('bypass node access')
      ->grantPermission('view published skos concept entities')
      ->grantPermission('view media')
      ->save();

    module_load_include('install', 'oe_content');
    oe_content_install(FALSE);

    $this->installEntitySchema('skos_concept');
    $this->installEntitySchema('skos_concept_scheme');

    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->nodeViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('node');
  }

}
