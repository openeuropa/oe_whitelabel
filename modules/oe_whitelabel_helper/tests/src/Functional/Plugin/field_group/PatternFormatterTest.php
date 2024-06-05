<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_helper\Functional\Plugin\field_group;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\SectionPatternAssert;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;

/**
 * Test the pattern field group formatter.
 */
class PatternFormatterTest extends WhitelabelBrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'field_group',
    'oe_whitelabel_helper',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create content type.
    $this->drupalCreateContentType([
      'name' => 'Test',
      'type' => 'test',
    ]);

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.test.default');

    // Create test fields.
    $fields = [
      'field_test_1' => 'Field 1',
      'field_test_2' => 'Field 2',
    ];
    foreach ($fields as $field_name => $field_label) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'text',
      ]);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => 'test',
        'label' => $field_label,
      ]);
      $instance->save();

      // Set the field visible on the display object.
      $display->setComponent($field_name, [
        'label' => 'above',
        'type' => 'text_default',
      ]);
    }

    // Save display + create node.
    $display->save();
  }

  /**
   * Test description list pattern formatter.
   */
  public function testDescriptionListPatternFormatter() {
    $page = $this->getSession()->getPage();

    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test_1',
        1 => 'field_test_2',
      ],
      'label' => 'Test label',
      'format_type' => 'oe_whitelabel_helper_description_list_pattern',
    ];
    $group = $this->createGroup('node', 'test', 'view', 'default', $data);
    field_group_group_save($group);

    $this->drupalCreateNode([
      'type' => 'test',
      'field_test_1' => [
        ['value' => 'Content test 1'],
      ],
      'field_test_2' => [
        ['value' => 'Content test 2'],
      ],
    ]);

    // Assert that fields are rendered using the field list horizontal pattern.
    $this->drupalGet('node/1');

    $description_lists = $page->findAll('css', '.bcl-description-list');
    $this->assertCount(1, $description_lists);

    $description_list_assert = new DescriptionListAssert();
    $description_list_assert->assertPattern([
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
    ], $description_lists[0]->getOuterHtml());
  }

  /**
   * Tests the section pattern formatter.
   */
  public function testSectionPatternFormatter(): void {
    $page = $this->getSession()->getPage();

    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test_1',
      ],
      'label' => 'First section',
      'format_type' => 'oe_whitelabel_section_pattern',
    ];
    $group = $this->createGroup('node', 'test', 'view', 'default', $data);
    field_group_group_save($group);

    // Create another section which will have empty content.
    $data = [
      'weight' => '2',
      'children' => [
        0 => 'field_test_2',
      ],
      'label' => 'Empty section',
      'format_type' => 'oe_whitelabel_section_pattern',
    ];
    $group = $this->createGroup('node', 'test', 'view', 'default', $data);
    field_group_group_save($group);

    $this->drupalCreateNode([
      'type' => 'test',
      'field_test_1' => [
        ['value' => 'Content test 1'],
      ],
      // Leave the second field empty.
      'field_test_2' => [
        ['value' => ''],
      ],
    ]);

    // Assert that fields are rendered using the field list horizontal pattern.
    $this->drupalGet('node/1');

    $sections = $page->findAll('css', 'section.section');
    // Only the first field group is shown.
    $this->assertCount(1, $sections);

    (new SectionPatternAssert())->assertPattern([
      'heading' => 'First section',
      'content' => '<div class="test___"> <div class="field__label fw-bold"> Field 1 </div> <div class="field__item"><p>Content test 1</p> </div> </div>',
      'tag' => 'section',
      'heading_tag' => 'h2',
      'attributes' => [
        'class' => 'mb-5 section',
      ],
      'heading_attributes' => [],
      'wrapper_attributes' => [],
    ], $sections[0]->getOuterHtml());
  }

}
