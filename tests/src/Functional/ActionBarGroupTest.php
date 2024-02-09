<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\oe_whitelabel\Traits\NodeFieldDisplayTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests for Action Bar group.
 */
class ActionBarGroupTest extends WhitelabelBrowserTestBase {

  use FieldGroupTestTrait;
  use NodeFieldDisplayTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'oe_whitelabel_starter_event',
    'oe_whitelabel_starter_news',
    'oe_whitelabel_extra_project',
    'oe_whitelabel_starter_person',
    'oe_whitelabel_starter_publication',
  ];

  /**
   * Test Action Bar group in content types.
   */
  public function testActionBarGroups(): void {
    // Test that groups exists in content types.
    $this->doEmptyGroupTest('oe_sc_event', 'oe_w_content_banner');
    $this->doEmptyGroupTest('oe_sc_news', 'oe_w_content_banner');
    $this->doEmptyGroupTest('oe_sc_person', 'oe_w_content_banner');
    $this->doEmptyGroupTest('oe_sc_publication', 'oe_w_content_banner');
    $this->doEmptyGroupTest('oe_project', 'oe_w_content_banner');

    // Test to place a hidden field in the group.
    $this->assertHiddenNodeDisplayField('oe_sc_event', 'oe_w_content_banner', 'oe_content_short_title');
    $this->doFieldsGroupTest('oe_sc_event', 'oe_w_content_banner', ['oe_content_short_title']);

    // Test to place a content field in the group.
    $this->assertSettingsNodeDisplayField([
      'type' => 'text_default',
      'label' => 'hidden',
      'settings' => [],
      'third_party_settings' => [],
      'region' => 'content',
    ], 'oe_sc_news', 'oe_w_content_banner', 'oe_summary');
    $this->doFieldsGroupTest('oe_sc_news', 'oe_w_content_banner', ['oe_summary']);

    // Test to place multiple text fields in the group.
    $this->doFieldsGroupTest('oe_sc_person', 'oe_w_content_banner', [
      'oe_sc_person_first_name',
      'oe_sc_person_last_name',
      'oe_sc_person_position',
      'oe_summary',
    ]);

    // Test place a new field in the group.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'oe_sc_publication',
      'label' => $this->randomMachineName(),
    ])->save();

    $this->assertHiddenNodeDisplayField('oe_sc_publication', 'oe_w_content_banner', 'field_test');
    $this->doFieldsGroupTest('oe_sc_publication', 'oe_w_content_banner', ['field_test']);

    // Test new field and existing.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'oe_project',
      'label' => $this->randomMachineName(),
    ])->save();
    $this->doFieldsGroupTest('oe_project', 'oe_w_content_banner', [
      'field_test',
      'oe_cx_achievements_and_milestone',
      'oe_cx_impacts',
    ]);
  }

  /**
   * Test that the Action Bar group exists in the display and has no children.
   *
   * @param string $bundle
   *   The node type bundle.
   * @param string $display
   *   The display of the node type.
   */
  protected function doEmptyGroupTest(string $bundle, string $display): void {
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load($bundle);
    if (empty($node_type)) {
      throw new \InvalidArgumentException(sprintf('A bundle of type node is expected, %s not found.', $bundle));
    }

    // Test that the group exists in content region and has no children.
    $group = field_group_load_field_group('group_action_bar', 'node', $bundle, 'view', $display);
    $this->assertEquals('content', $group->region);
    $this->assertEquals([], $group->children);

    // Test that the group is not rendered given has no children.
    $node = $this->createNode(['type' => $bundle]);
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $display);
    $html = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($html->__toString());
    $group_wrapper = $crawler->filter("d-flex.justify-content-end.mt-2.align-items-center");
    $this->assertCount(0, $group_wrapper);
  }

  /**
   * Test that fields can be added to Action Bar group and are displayed.
   *
   * The function only accepts text fields.
   *
   * @param string $bundle
   *   The node type bundle.
   * @param string $display
   *   The display of the node type.
   * @param array $fields
   *   The fields to be checked.
   */
  protected function doFieldsGroupTest(string $bundle, string $display, array $fields): void {
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load($bundle);
    if (empty($node_type)) {
      throw new \InvalidArgumentException(sprintf('A bundle of type node is expected, %s not found.', $bundle));
    }

    if (empty($fields)) {
      throw new \InvalidArgumentException("An array with field names is expected");
    }

    $bundle_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle);
    if ($diff = array_diff($fields, array_keys($bundle_fields))) {
      throw new \InvalidArgumentException(sprintf('The %s bundle does not have the expected fields: %s.', $bundle, $diff = implode(', ', $diff)));
    }

    // Test that the fields can be added to the group.
    $node_fields = [];
    foreach ($fields as $field) {
      $node_fields[$field] = "$field " . $this->randomMachineName();
    }
    $node = $this->createNode(['type' => $bundle] + $node_fields);

    $group = field_group_load_field_group('group_action_bar', 'node', $bundle, 'view', $display);
    $this->drupalLogin($this->createUser([
      'administer content types',
      'administer node display',
    ]));

    $form_fields = [];
    foreach ($fields as $field) {
      $form_fields += [
        "fields[$field][label]" => 'hidden',
        "fields[$field][region]" => $group->region,
        "fields[$field][parent]" => $group->group_name,
      ];
    }
    $this->drupalGet("admin/structure/types/manage/$bundle/display/$display");
    $this->submitForm($form_fields, 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $group = field_group_load_field_group('group_action_bar', 'node', $bundle, 'view', $display);
    $this->assertEmpty(array_diff($fields, $group->children));

    // Test render of the group together with it's children.
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $display);
    $html = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($html->__toString());
    $group_wrapper = $crawler->filter(".d-flex.justify-content-end.mt-2.align-items-center");
    $this->assertCount(1, $group_wrapper);
    $this->assertCount(count($fields), $group_wrapper->children());

    // Check that the children texts are the same that the node field values.
    $node_values = array_flip($node_fields);
    foreach ($group_wrapper->children() as $child) {
      $field_value = $child->textContent;
      $this->assertArrayHasKey($field_value, $node_values);
      unset($node_values[$field_value]);
    }
  }

}
