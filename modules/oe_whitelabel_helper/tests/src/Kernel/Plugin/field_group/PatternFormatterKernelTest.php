<?php

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\oe_bootstrap_theme\Kernel\Traits\RenderTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\SectionPatternAssert;
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
      'field_test_3' => 'Field 3',
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

  /**
   * Tests the section and sub-section pattern formatters.
   */
  public function testSectionPatternFormatter(): void {
    // Create a section field group.
    $group_data = [
      'weight' => '1',
      'children' => [
        'field_test_1',
        'field_test_2',
      ],
      'label' => 'First section',
      'format_type' => 'oe_whitelabel_section_pattern',
    ];
    $group = $this->createGroup('node', 'article', 'view', 'default', $group_data);
    field_group_group_save($group);

    // Create a sub-section field group.
    $group_data = [
      'weight' => '2',
      'children' => [
        'field_test_3',
      ],
      'label' => 'First sub-section',
      'format_type' => 'oe_whitelabel_sub_section_pattern',
    ];
    $group = $this->createGroup('node', 'article', 'view', 'default', $group_data);
    field_group_group_save($group);

    // Create a test entity.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Example article',
      'field_test_1' => 'Content test 1',
      'field_test_2' => 'Content test 2',
      'field_test_3' => 'Content test 3',
    ]);
    $node->save();

    $element = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'default');

    $html = $this->renderRoot($element);

    $crawler = new Crawler($html);

    $sections = $crawler->filter('section.section');

    $this->assertCount(2, $sections);

    (new SectionPatternAssert())->assertPattern([
      'heading' => 'First section',
      // The field markup is overly specific, but that's ok.
      'content' => '<div class="article__field-test-1"> <div class="field__label fw-bold"> Field 1 </div> <div class="field__item">Content test 1</div> </div> <div class="article__field-test-2"> <div class="field__label fw-bold"> Field 2 </div> <div class="field__item">Content test 2</div> </div>',
      'tag' => 'section',
      'heading_tag' => 'h2',
      'attributes' => [
        'class' => 'mb-5 section',
      ],
      'heading_attributes' => [],
      'wrapper_attributes' => [],
    ], $sections->eq(0)->outerHtml());

    (new SectionPatternAssert())->assertPattern([
      'heading' => 'First sub-section',
      'content' => '<div class="article__field-test-3"> <div class="field__label fw-bold"> Field 3 </div> <div class="field__item">Content test 3</div> </div>',
      'tag' => 'section',
      'heading_tag' => 'h3',
      'attributes' => [
        'class' => 'mb-4 section',
      ],
      'heading_attributes' => [],
      'wrapper_attributes' => [],
    ], $sections->eq(1)->outerHtml());

    // Now test with empty field values.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Empty article',
      'field_test_1' => '',
      'field_test_2' => '',
      'field_test_3' => '',
    ]);
    $node->save();

    $element = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'default');

    $html = $this->renderRoot($element);

    $crawler = new Crawler($html);

    $sections = $crawler->filter('section.section');

    // The field groups are not printed.
    $this->assertCount(0, $sections);
  }

}
