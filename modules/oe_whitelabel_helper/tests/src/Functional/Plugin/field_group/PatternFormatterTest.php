<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Functional\Plugin\field_group;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

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
    'field_ui',
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

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

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
    $assert_session = $this->assertSession();

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
    $element_selector = 'dl';
    $this->assertSession()->elementExists('css', $element_selector);
    $assert_session->elementTextContains('css', $element_selector . ' div dt', 'Field 1');
    $assert_session->elementTextContains('css', $element_selector . ' dd:nth-child(2)', 'Content test 1');
    $assert_session->elementTextContains('css', $element_selector . ' div:nth-child(3) dt', 'Field 2');
    $assert_session->elementTextContains('css', $element_selector . ' dd:nth-child(4)', 'Content test 2');
  }

}