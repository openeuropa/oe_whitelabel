<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_link_lists\Functional;

use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CardPatternAssert;
use Drupal\Tests\oe_whitelabel\Functional\WhitelabelBrowserTestBase;
use Drupal\Tests\oe_whitelabel\Traits\NodeCreationTrait as WhitelabelNodeCreationTrait;
use Drupal\oe_link_lists\Entity\LinkList;

/**
 * Tests the teaser display plugin with the manual list bundle.
 */
class TeaserDisplayManualLinkListTest extends WhitelabelBrowserTestBase {

  use WhitelabelNodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_link_lists',
    'oe_link_lists_manual_source',
    'oe_whitelabel_starter_event',
    'oe_whitelabel_starter_news',
    'oe_whitelabel_starter_person',
    'oe_whitelabel_starter_publication',
    'oe_whitelabel_extra_project',
  ];

  /**
   * Tests the manual link rendering for supported bundles.
   */
  public function testManualLinkList(): void {
    $news = $this->createNewsNode();
    $event = $this->createEventNode();
    $person = $this->createPersonNode();
    $publication = $this->createPublicationNode();
    $project = $this->createProjectNode();

    $link_storage = \Drupal::entityTypeManager()->getStorage('link_list_link');
    /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface[] $links */
    $links = [];
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $news->id(),
      'status' => 1,
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $event->id(),
      'status' => 1,
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $person->id(),
      'status' => 1,
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $publication->id(),
      'status' => 1,
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $project->id(),
      'status' => 1,
    ]);
    // Create a link for each entity already referenced, but applying overrides.
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $news->id(),
      'status' => 1,
      'title' => $this->randomString(),
      'teaser' => $this->randomString(),
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $event->id(),
      'status' => 1,
      'title' => $this->randomString(),
      'teaser' => $this->randomString(),
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $person->id(),
      'status' => 1,
      'title' => $this->randomString(),
      'teaser' => $this->randomString(),
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $publication->id(),
      'status' => 1,
      'title' => $this->randomString(),
      'teaser' => $this->randomString(),
    ]);
    $links[] = $link_storage->create([
      'bundle' => 'internal',
      'target' => $project->id(),
      'status' => 1,
      'title' => $this->randomString(),
      'teaser' => $this->randomString(),
    ]);
    // Save all the links created at once.
    array_walk($links, fn($link) => $link->save());

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = LinkList::create([
      'bundle' => 'manual',
      'title' => 'Link list title',
      'administrative_title' => 'Test',
      'configuration' => [
        'source' => [
          'plugin' => 'manual_links',
          'plugin_configuration' => [
            'links' => array_map(fn($link) => [
              'entity_id' => $link->id(),
              'entity_revision_id' => $link->getRevisionId(),
            ], $links),
          ],
        ],
        'display' => [
          'plugin' => 'oewt_teaser',
          'plugin_configuration' => [
            'columns' => 1,
            'title' => 'Main title',
            'more' => [],
          ],
        ],
      ],
    ]);
    $link_list->save();

    $editor = $this->drupalCreateUser([
      'view link list',
      'access link list canonical page',
    ]);
    $this->drupalLogin($editor);
    $this->drupalGet($link_list->toUrl());

    $assert_session = $this->assertSession();
    $section = $assert_session->elementExists('css', '#block-oe-whitelabel-main-page-content > section');
    /** @var \Behat\Mink\Element\NodeElement[] $items */
    $items = $section->findAll('css', '.columns > .columns__item');
    $this->assertCount(10, $items);
    $card_assert = new CardPatternAssert();
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $news->label(),
      'url' => $news->toUrl()->toString(),
      'description' => 'News summary.',
      'content' => ['09 February 2022'],
    ], $items[0]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $event->label(),
      'url' => $event->toUrl()->toString(),
      'description' => 'Event summary.',
      'content' => ['10 Feb 2022', 'Brussel, Belgium'],
    ], $items[1]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $person->label(),
      'url' => $person->toUrl()->toString(),
      'content' => ['DG Test', 'Director'],
    ], $items[2]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $publication->label(),
      'url' => $publication->toUrl()->toString(),
      'description' => 'This is an example summary.',
      'content' => ['02 August 2022'],
    ], $items[3]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $project->label(),
      'url' => $project->toUrl()->toString(),
      'description' => 'Project teaser text',
      'content' => ['10 May 2020', '15 May 2025'],
    ], $items[4]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[5]->getTitle(),
      'url' => $news->toUrl()->toString(),
      'description' => $links[5]->getTeaser(),
      'content' => ['09 February 2022'],
    ], $items[5]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[6]->getTitle(),
      'url' => $event->toUrl()->toString(),
      'description' => $links[6]->getTeaser(),
      'content' => ['10 Feb 2022', 'Brussel, Belgium'],
    ], $items[6]->getOuterHtml());
    // The person bundle doesn't render any teaser.
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[7]->getTitle(),
      'url' => $person->toUrl()->toString(),
      'content' => ['DG Test', 'Director'],
    ], $items[7]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[8]->getTitle(),
      'url' => $publication->toUrl()->toString(),
      'description' => $links[8]->getTeaser(),
      'content' => ['02 August 2022'],
    ], $items[8]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[9]->getTitle(),
      'url' => $project->toUrl()->toString(),
      'description' => $links[9]->getTeaser(),
      'content' => ['10 May 2020', '15 May 2025'],
    ], $items[9]->getOuterHtml());

    // Test that correct cache information is propagated in the render arrays.
    $random_title = $this->randomString();
    $news->setTitle($random_title)->save();
    $this->drupalGet($link_list->toUrl());
    $items = $section->findAll('css', '.columns > .columns__item');
    $this->assertCount(10, $items);
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $random_title,
      'url' => $news->toUrl()->toString(),
      'description' => 'News summary.',
      'content' => ['09 February 2022'],
    ], $items[0]->getOuterHtml());
    $card_assert->assertPattern([
      'variant' => 'search',
      'title' => $links[5]->getTitle(),
      'url' => $news->toUrl()->toString(),
      'description' => $links[5]->getTeaser(),
      'content' => ['09 February 2022'],
    ], $items[5]->getOuterHtml());
  }

}
