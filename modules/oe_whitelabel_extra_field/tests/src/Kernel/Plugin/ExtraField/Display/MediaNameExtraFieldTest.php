<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_extra_field\Kernel\Plugin\ExtraField\Display;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Tests the MediaNameExtraField plugin.
 *
 * @coversDefaultClass \Drupal\oe_whitelabel_extra_field\Plugin\ExtraField\Display\MediaNameExtraField
 * @group oe_whitelabel_extra_field
 */
class MediaNameExtraFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'extra_field',
    'extra_field_plus',
    'field',
    'file',
    'image',
    'media',
    'node',
    'oe_whitelabel_extra_field',
    'system',
    'user',
  ];

  /**
   * The entity view display.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected EntityViewDisplay $display;

  /**
   * The test media entity.
   *
   * @var \Drupal\media\Entity\Media
   */
  protected Media $media;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', 'file_usage');
    $this->installConfig(['system', 'media']);

    // Create a media type with a basic source plugin.
    $media_type = MediaType::create([
      'id' => 'document',
      'label' => 'Document',
      'source' => 'file',
      'source_configuration' => [],
      'field_map' => [],
    ]);
    $media_type->save();
    // Install the source field config created by the media type.
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();

    $this->display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'document',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $this->display->save();

    $this->media = Media::create([
      'bundle' => 'document',
      'name' => 'Test media name',
      'uid' => 0,
    ]);
    $this->media->save();
  }

  /**
   * Tests rendering with default settings (no link, span wrapper).
   *
   * @covers ::view
   */
  public function testDefaultSettings(): void {
    $this->setExtraFieldSettings([]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->media);

    $this->assertEquals('html_tag', $render['#type']);
    $this->assertEquals('span', $render['#tag']);
    $this->assertEquals('Test media name', $render['#value']);
    $this->assertEmpty($render['#attributes']);
  }

  /**
   * Tests rendering with a custom wrapper tag.
   *
   * @covers ::view
   */
  public function testCustomWrapper(): void {
    $this->setExtraFieldSettings([
      'wrapper' => 'h4',
      'link_to_entity' => FALSE,
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->media);

    $this->assertEquals('html_tag', $render['#type']);
    $this->assertEquals('h4', $render['#tag']);
    $this->assertEquals('Test media name', $render['#value']);
  }

  /**
   * Tests rendering with HTML attributes.
   *
   * @covers ::view
   */
  public function testWithAttributes(): void {
    $this->setExtraFieldSettings([
      'attributes' => 'class="media-title" data-type="doc"',
      'link_to_entity' => FALSE,
      'wrapper' => 'div',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->media);

    $this->assertEquals('div', $render['#tag']);
    $this->assertEquals('media-title', $render['#attributes']['class']);
    $this->assertEquals('doc', $render['#attributes']['data-type']);
  }

  /**
   * Tests rendering with link to entity.
   *
   * @covers ::view
   */
  public function testLinkToEntity(): void {
    $this->setExtraFieldSettings([
      'link_to_entity' => TRUE,
      'wrapper' => 'h3',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->media);

    $this->assertEquals('<h3>', $render['#prefix']);
    $this->assertEquals('</h3>', $render['#suffix']);
    $this->assertEquals('Test media name', $render['#title']);
    $this->assertStringContainsString('/media/' . $this->media->id(), $render['#url']->toString());
  }

  /**
   * Tests rendering with link to entity and attributes.
   *
   * @covers ::view
   */
  public function testLinkToEntityWithAttributes(): void {
    $this->setExtraFieldSettings([
      'link_to_entity' => TRUE,
      'wrapper' => 'span',
      'attributes' => 'class="media-link"',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->media);

    $this->assertEquals('<span>', $render['#prefix']);
    $this->assertEquals('</span>', $render['#suffix']);
    $this->assertEquals('media-link', $render['#attributes']['class']);
  }

  /**
   * Sets extra field settings on the entity view display.
   *
   * @param array $settings
   *   The settings to set.
   */
  protected function setExtraFieldSettings(array $settings): void {
    $this->display->setComponent('extra_field_extra_media_title', [
      'settings' => $settings,
    ]);
    $this->display->save();
  }

  /**
   * Creates and returns the MediaNameExtraField plugin instance.
   *
   * @return \Drupal\oe_whitelabel_extra_field\Plugin\ExtraField\Display\MediaNameExtraField
   *   The plugin instance.
   */
  protected function getPlugin() {
    /** @var \Drupal\extra_field\Plugin\ExtraFieldDisplayManager $manager */
    $manager = $this->container->get('plugin.manager.extra_field_display');
    $plugin = $manager->createInstance('extra_media_title');
    $plugin->setEntity($this->media);
    $plugin->setEntityViewDisplay($this->display);
    $plugin->setViewMode('default');
    return $plugin;
  }

}
