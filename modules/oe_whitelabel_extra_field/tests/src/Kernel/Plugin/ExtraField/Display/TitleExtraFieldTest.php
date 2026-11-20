<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_extra_field\Kernel\Plugin\ExtraField\Display;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the TitleExtraField plugin.
 *
 * @coversDefaultClass \Drupal\oe_whitelabel_extra_field\Plugin\ExtraField\Display\TitleExtraField
 * @group oe_whitelabel_extra_field
 */
class TitleExtraFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'extra_field',
    'extra_field_plus',
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
   * The test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'node']);

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    $this->display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $this->display->save();

    $this->node = Node::create([
      'type' => 'article',
      'title' => 'Test node title',
      'uid' => 0,
    ]);
    $this->node->save();
  }

  /**
   * Tests rendering with default settings (no link, span wrapper).
   *
   * @covers ::view
   */
  public function testDefaultSettings(): void {
    $this->setExtraFieldSettings([]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->node);

    $this->assertEquals('html_tag', $render['#type']);
    $this->assertEquals('span', $render['#tag']);
    $this->assertEquals('Test node title', $render['#value']);
    $this->assertEmpty($render['#attributes']);
  }

  /**
   * Tests rendering with a custom wrapper tag.
   *
   * @covers ::view
   */
  public function testCustomWrapper(): void {
    $this->setExtraFieldSettings([
      'wrapper' => 'h2',
      'link_to_entity' => FALSE,
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->node);

    $this->assertEquals('html_tag', $render['#type']);
    $this->assertEquals('h2', $render['#tag']);
    $this->assertEquals('Test node title', $render['#value']);
  }

  /**
   * Tests rendering with HTML attributes.
   *
   * @covers ::view
   */
  public function testWithAttributes(): void {
    $this->setExtraFieldSettings([
      'attributes' => 'class="my-class" id="title-1"',
      'link_to_entity' => FALSE,
      'wrapper' => 'h3',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->node);

    $this->assertEquals('h3', $render['#tag']);
    $this->assertEquals('my-class', $render['#attributes']['class']);
    $this->assertEquals('title-1', $render['#attributes']['id']);
  }

  /**
   * Tests rendering with link to entity.
   *
   * @covers ::view
   */
  public function testLinkToEntity(): void {
    $this->setExtraFieldSettings([
      'link_to_entity' => TRUE,
      'wrapper' => 'h2',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->node);

    $this->assertEquals('<h2>', $render['#prefix']);
    $this->assertEquals('</h2>', $render['#suffix']);
    $this->assertEquals('Test node title', $render['#title']);
    $this->assertStringContainsString('/node/' . $this->node->id(), $render['#url']->toString());
  }

  /**
   * Tests rendering with link to entity and attributes.
   *
   * @covers ::view
   */
  public function testLinkToEntityWithAttributes(): void {
    $this->setExtraFieldSettings([
      'link_to_entity' => TRUE,
      'wrapper' => 'div',
      'attributes' => 'class="custom-link"',
    ]);

    $plugin = $this->getPlugin();
    $render = $plugin->view($this->node);

    $this->assertEquals('<div>', $render['#prefix']);
    $this->assertEquals('</div>', $render['#suffix']);
    $this->assertEquals('custom-link', $render['#attributes']['class']);
  }

  /**
   * Sets extra field settings on the entity view display.
   *
   * @param array $settings
   *   The settings to set.
   */
  protected function setExtraFieldSettings(array $settings): void {
    $this->display->setComponent('extra_field_extra_title', [
      'settings' => $settings,
    ]);
    $this->display->save();
  }

  /**
   * Creates and returns the TitleExtraField plugin instance.
   *
   * @return \Drupal\oe_whitelabel_extra_field\Plugin\ExtraField\Display\TitleExtraField
   *   The plugin instance.
   */
  protected function getPlugin() {
    /** @var \Drupal\extra_field\Plugin\ExtraFieldDisplayManager $manager */
    $manager = $this->container->get('plugin.manager.extra_field_display');
    $plugin = $manager->createInstance('extra_title');
    $plugin->setEntity($this->node);
    $plugin->setEntityViewDisplay($this->display);
    $plugin->setViewMode('default');
    return $plugin;
  }

}
