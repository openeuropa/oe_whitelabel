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
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $nodeViewBuilder;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'composite_reference',
    'datetime',
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
    'node',
    'oe_content',
    'oe_content_departments_field',
    'oe_content_documents_field',
    'oe_content_entity',
    'oe_content_entity_contact',
    'oe_content_entity_organisation',
    'oe_content_reference_code_field',
    'oe_media',
    'options',
    'rdf_skos',
    'sparql_entity_storage',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpSparql();

    $this->installEntitySchema('node');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');

    $this->container->get('module_handler')->loadInclude('oe_content_documents_field', 'install');
    oe_content_documents_field_install(FALSE);

    $this->installConfig([
      'node',
      'filter',
      'oe_media',
      'oe_content',
      'oe_content_entity',
      'oe_content_entity_organisation',
      'oe_content_departments_field',
      'oe_content_reference_code_field',
    ]);

    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view published skos concept entities')
      ->grantPermission('view media')
      ->save();

    module_load_include('install', 'oe_content');
    oe_content_install(FALSE);

    $this->nodeViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('node');
  }

}
