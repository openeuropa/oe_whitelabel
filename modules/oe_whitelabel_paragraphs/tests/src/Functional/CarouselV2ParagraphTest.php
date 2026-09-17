<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_whitelabel\Traits\MediaCreationTrait;

/**
 * Tests editing and displaying both Carousel V2 layouts.
 *
 * @group batch1
 */
class CarouselV2ParagraphTest extends BrowserTestBase {

  use MediaCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'composite_reference', 'oe_whitelabel_paragraphs'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'carousel_test']);
    $storage = FieldStorageConfig::create([
      'field_name' => 'oe_w_paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => -1,
      'settings' => ['target_type' => 'paragraph'],
    ]);
    $storage->save();
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'carousel_test',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => ['oe_carousel_v2' => 'oe_carousel_v2']],
      ],
    ])->save();
    $displays = \Drupal::service('entity_display.repository');
    $displays->getFormDisplay('node', 'carousel_test')
      ->setComponent('oe_w_paragraphs', ['type' => 'oe_paragraphs_variants'])->save();
    $displays->getViewDisplay('node', 'carousel_test')
      ->setComponent('oe_w_paragraphs', ['type' => 'entity_reference_revisions_entity_view'])->save();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests required layout, minimum items, saved values and SDC rendering.
   */
  public function testLayouts(): void {
    $media = $this->createImageMedia(['name' => 'Carousel image']);
    foreach (['split', 'full_width'] as $layout) {
      $this->drupalGet('/node/add/carousel_test');
      $this->submitForm([], 'Add Carousel V2');
      $prefix = 'oe_w_paragraphs[0][subform]';
      $select = $this->assertSession()->fieldExists($prefix . '[oe_w_carousel_layout]');
      $this->assertTrue($select->hasAttribute('required'));
      $this->assertSession()->optionExists($prefix . '[oe_w_carousel_layout]', 'Image alongside content');
      $this->assertSession()->optionExists($prefix . '[oe_w_carousel_layout]', 'Full-width image');
      $values = [
        'title[0][value]' => 'Carousel ' . $layout,
        $prefix . '[oe_w_carousel_layout]' => $layout,
        $prefix . '[field_oe_carousel_items][0][subform][field_oe_title][0][value]' => 'First slide',
        $prefix . '[field_oe_carousel_items][0][subform][field_oe_text][0][value]' => 'First caption',
        $prefix . '[field_oe_carousel_items][0][subform][field_oe_media][0][target_id]' => $media->label() . ' (' . $media->id() . ')',
      ];
      $this->submitForm($values, 'Save');
      $this->assertSession()->pageTextContains('The Carousel paragraph should contain at least 2 items.');
      $this->submitForm([], 'Add Carousel item');
      $values += [
        $prefix . '[field_oe_carousel_items][1][subform][field_oe_title][0][value]' => 'Second slide',
        $prefix . '[field_oe_carousel_items][1][subform][field_oe_media][0][target_id]' => $media->label() . ' (' . $media->id() . ')',
      ];
      $this->submitForm($values, 'Save');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->elementExists('css', '.bcl-carousel-v2--' . $layout);
      $this->assertSession()->elementsCount('css', '.bcl-carousel-v2 .carousel-item', 2);
      $this->assertSession()->pageTextContains('First slide');
      $this->assertSession()->pageTextContains('Second slide');
      $node = $this->drupalGetNodeByTitle('Carousel ' . $layout);
      $this->assertSame($layout, $node->get('oe_w_paragraphs')->entity->get('oe_w_carousel_layout')->value);
    }
  }

}
