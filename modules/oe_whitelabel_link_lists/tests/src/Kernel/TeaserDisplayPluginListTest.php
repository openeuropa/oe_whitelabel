<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_link_lists\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CardPatternAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\ColumnsPatternAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\SectionPatternAssert;
use Drupal\Tests\oe_whitelabel\Kernel\AbstractKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestNoBundleWithLabel;
use Drupal\oe_link_lists\DefaultEntityLink;
use Drupal\oe_link_lists\DefaultLink;
use Drupal\oe_link_lists\LinkCollection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the teaser display plugin.
 */
class TeaserDisplayPluginListTest extends AbstractKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'oe_whitelabel_link_lists',
    'oe_link_lists',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_no_bundle_with_label');
    // Create two bundles for the entity_test entity type.
    entity_test_create_bundle('foo');
    entity_test_create_bundle('bar');

    // Create the teaser view mode.
    EntityViewMode::create([
      'id' => 'entity_test.teaser',
      'targetEntityType' => 'entity_test',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'Teaser',
    ])->save();
    // Enable it for the foo bundle.
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'bar',
      'mode' => 'teaser',
      'status' => TRUE,
    ]);
    $display->save();
  }

  /**
   * Tests the rendering of the display.
   */
  public function testDisplayRendering(): void {
    $foo_entity = EntityTest::create([
      'name' => 'Foo 1',
      'type' => 'foo',
    ]);
    $foo_entity->save();
    $bar_entity = EntityTest::create([
      'name' => 'Bar 1',
      'type' => 'bar',
    ]);
    $bar_entity->save();
    $no_bundle_entity = EntityTestNoBundleWithLabel::create([
      'name' => 'No bundle 1',
    ]);
    $no_bundle_entity->save();

    // A link with data from foo entity.
    $link_one = new DefaultEntityLink($foo_entity->toUrl(), 'Custom label for foo', []);
    $link_one->setEntity($foo_entity);
    // A link with custom teaser. We can reuse the same entity, as each link
    // rendered is independent.
    $teaser = $this->randomString();
    $link_two = new DefaultEntityLink($foo_entity->toUrl(), $foo_entity->label(), [
      '#plain_text' => $teaser,
    ]);
    $link_two->setEntity($foo_entity);
    // A link with an entity with no bundle. Since there is no display for this
    // entity, we can set a custom URL and it will be rendered in the card
    // pattern. This does not happen when the view mode is used.
    $link_three = new DefaultEntityLink(Url::fromRoute('<front>'), $no_bundle_entity->label(), []);
    $link_three->setEntity($no_bundle_entity);
    // A link to an entity that has the teaser display.
    $link_four = new DefaultEntityLink($bar_entity->toUrl(), $bar_entity->label(), []);
    $link_four->setEntity($bar_entity);
    // A link with title overridden.
    // Covers oe_whitelabel_link_lists_entity_build_defaults_alter()
    $link_five = new DefaultEntityLink($bar_entity->toUrl(), 'Custom bar entity label', []);
    $link_five->setEntity($bar_entity);

    $collection = new LinkCollection([
      $link_one,
      $link_two,
      $link_three,
      $link_four,
      $link_five,
      new DefaultLink(Url::fromUri('https://www.example.com'), 'A link without entity', []),
    ]);

    /** @var \Drupal\oe_link_lists\LinkDisplayInterface $instance */
    $instance = $this->container->get('plugin.manager.oe_link_lists.link_display')->createInstance('oewt_teaser', [
      'columns' => 3,
      'title' => 'Main title',
      'more' => [],
    ]);
    $build = $instance->build($collection);
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    (new SectionPatternAssert())->assertPattern([
      'tag' => 'section',
      'heading_tag' => 'h2',
      'heading' => 'Main title',
      'attributes' => [
        'class' => 'link-list-display--oewt-teaser section',
      ],
    ], $html);

    $crawler = new Crawler($html);
    $content = $crawler->filter('.section__body');
    (new ColumnsPatternAssert())->assertPattern([
      'columns' => 3,
      'tag' => 'div',
    ], $content->html());

    $items = $content->filter('.columns > .columns__item');
    $this->assertCount(6, $items);
    $card_assert = new CardPatternAssert();
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => 'Custom label for foo',
      'url' => $foo_entity->toUrl()->toString(),
      'description' => NULL,
      'badges' => [],
    ], $items->eq(0)->html());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => 'Foo 1',
      'url' => $foo_entity->toUrl()->toString(),
      'description' => $teaser,
      'badges' => [],
    ], $items->eq(1)->html());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => 'No bundle 1',
      'url' => '/',
      'description' => NULL,
      'badges' => [],
    ], $items->eq(2)->html());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => 'A link without entity',
      'url' => 'https://www.example.com',
      'description' => NULL,
      'badges' => [],
    ], $items->eq(5)->html());

    $fn_normalise_html = fn($html) => trim(preg_replace('/\s+/u', ' ', $html));
    $this->assertEquals('teaser | Bar 1 <div class="bar__name">Bar 1</div>', $fn_normalise_html($items->eq(3)->html()));
    $this->assertEquals('teaser | Custom bar entity label <div class="bar__name">Custom bar entity label</div>', $fn_normalise_html($items->eq(4)->html()));
  }

  /**
   * Tests that overrides applied in the plugin do not impact original entities.
   */
  public function testOverriddenEntityMechanism(): void {
    $entity = EntityTest::create([
      'name' => 'Original entity title',
      'type' => 'bar',
    ]);
    $entity->save();

    $link = new DefaultEntityLink($entity->toUrl(), 'Overridden title', []);
    $link->setEntity($entity);
    $collection = new LinkCollection([$link]);

    /** @var \Drupal\oe_link_lists\LinkDisplayInterface $instance */
    $instance = $this->container->get('plugin.manager.oe_link_lists.link_display')->createInstance('oewt_teaser', [
      'columns' => 1,
      'title' => NULL,
      'more' => [],
    ]);
    $build = $instance->build($collection);

    // The original entity should not have its values changed.
    $this->assertEquals('Original entity title', $entity->label());
    // The original entity can be saved without issues.
    $entity->save();

    // The entity can be found in the render array.
    $overridden_entity = $build['content']['#content']['#items'][0]['entity']['#entity_test'];
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('This instance of entity object has been overridden and should not be saved.');
    $overridden_entity->save();
  }

}
