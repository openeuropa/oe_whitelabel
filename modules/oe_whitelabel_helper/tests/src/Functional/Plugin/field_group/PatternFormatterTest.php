<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_helper\Functional\Plugin\field_group;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\InPageNavigationAssert;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test the pattern field group formatter.
 */
class PatternFormatterTest extends BrowserTestBase {

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
  protected $defaultTheme = 'oe_whitelabel';

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
   * Tests inpage navigation anchors generated from translated labels.
   *
   * @param array $labels
   *   The field labels.
   * @param array $ids
   *   The expected field label IDs.
   *
   * @dataProvider inpageNavigationLabelsProvider
   */
  public function testInpageNavigationPatternFormatter(array $labels, array $ids): void {
    foreach ($labels as $delta => $label) {
      FieldConfig::load('node.test.field_test_' . ($delta + 1))
        ->setLabel($label)
        ->save();
    }

    $group = $this->createGroup('node', 'test', 'view', 'default', [
      'weight' => '1',
      'children' => ['field_test_1', 'field_test_2'],
      'label' => 'Page content',
      'format_type' => 'oe_whitelabel_helper_inpage_navigation_pattern',
    ]);
    field_group_group_save($group);

    $node = $this->drupalCreateNode([
      'type' => 'test',
      'field_test_1' => [['value' => 'Content test 1']],
      'field_test_2' => [['value' => 'Content test 2']],
    ]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    $links = [];
    foreach ($labels as $delta => $label) {
      $links[] = [
        'label' => $label,
        'href' => '#' . $ids[$delta],
      ];
      $this->assertSession()->elementTextEquals('css', '.field__label#' . $ids[$delta], $label);
    }

    $assert = new InPageNavigationAssert();
    $assert->assertPattern([
      'title' => 'Page content',
      'links' => $links,
    ], $this->assertSession()->elementExists('css', 'nav.bcl-inpage-navigation')->getOuterHtml());
  }

  /**
   * Provides translated labels and their expected navigation anchors.
   *
   * @return array
   *   The test cases.
   */
  public static function inpageNavigationLabelsProvider(): array {
    return [
      'English labels' => [
        ['Summary', 'Main content'],
        ['summary', 'main-content'],
      ],
      'Greek labels' => [
        ['Σύνοψη', 'Κύριο περιεχόμενο'],
        ['synopsi', 'kyrio-periehomeno'],
      ],
      'Duplicate Greek labels' => [
        ['Σύνοψη', 'Σύνοψη'],
        ['synopsi', 'synopsi--2'],
      ],
    ];
  }

}
