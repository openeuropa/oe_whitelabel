<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the color scheme.
 */
class ColorSchemeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_color_scheme',
    'oe_whitelabel_helper',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel_test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'test_ct',
      'name' => 'Test content type',
    ]);

    FieldStorageConfig::create([
      'field_name' => 'oe_w_colorscheme',
      'entity_type' => 'node',
      'type' => 'oe_color_scheme',
    ])->save();

    FieldConfig::create([
      'label' => 'ColorScheme field',
      'field_name' => 'oe_w_colorscheme',
      'entity_type' => 'node',
      'bundle' => 'test_ct',
    ])->save();

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'test_ct');
    $form_display = $form_display->setComponent('oe_w_colorscheme', [
      'region' => 'content',
      'type' => 'oe_color_scheme_widget',
    ]);
    $form_display->save();
  }

  /**
   * Tests that the color scheme is injected into node and paragraphs.
   */
  public function testColorScheme(): void {
    $node = Node::create([
      'type' => 'test_ct',
      'title' => 'Test',
      'oe_w_colorscheme' => [
        'name' => 'foo_bar',
      ],
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $assert_session = $this->assertSession();

    $assert_session->elementExists('css', '.foo_bar');
  }

}
