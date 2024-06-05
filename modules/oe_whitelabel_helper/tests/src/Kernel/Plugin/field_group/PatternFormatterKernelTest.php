<?php

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\oe_bootstrap_theme\Kernel\Traits\RenderTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\Tests\oe_whitelabel\Kernel\AbstractKernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests field group formatters that use UI patterns.
 */
class PatternFormatterKernelTest extends AbstractKernelTestBase {

  use FieldGroupTestTrait;
  use RenderTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'field_group',
    'oe_whitelabel_helper',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $type = NodeType::create([
      'name' => 'Article',
      'type' => 'article',
    ]);
    $status = $type->save();
    $this->assertSame(SAVED_NEW, $status);

    $this->installConfig(['field', 'field_group']);
    $this->installEntitySchema('node');

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $entity_display_repository->getViewDisplay('node', 'article');

    // Create test fields.
    $fields = [
      'field_test_1' => 'Field 1',
      'field_test_2' => 'Field 2',
    ];
    foreach ($fields as $field_name => $field_label) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'string',
      ]);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => 'article',
        'label' => $field_label,
      ]);
      $instance->save();

      // Set the field visible on the display object.
      $display->setComponent($field_name, [
        'label' => 'above',
        'type' => 'string',
      ]);
    }

    $display->save();
  }

  /**
   * Tests the description list pattern formatter.
   */
  public function testDescriptionListPatternFormatter() {
    // Create a description list field group.
    $group_data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test_1',
        1 => 'field_test_2',
      ],
      'label' => 'Test label',
      'format_type' => 'oe_whitelabel_helper_description_list_pattern',
    ];
    $group = $this->createGroup('node', 'article', 'view', 'default', $group_data);
    field_group_group_save($group);

    // Create a test entity.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Example article',
      'field_test_1' => 'Content test 1',
      'field_test_2' => 'Content test 2',
    ]);
    $node->save();

    $element = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'default');

    $html = $this->renderRoot($element);

    $crawler = new Crawler($html);

    $description_lists = $crawler->filter('.bcl-description-list');

    $this->assertCount(1, $description_lists);

    (new DescriptionListAssert())->assertPattern([
      'items' => [
        [
          'term' => 'Field 1',
          'definition' => 'Content test 1',
        ],
        [
          'term' => 'Field 2',
          'definition' => 'Content test 2',
        ],
      ],
    ], $description_lists->eq(0)->outerHtml());
  }

}
