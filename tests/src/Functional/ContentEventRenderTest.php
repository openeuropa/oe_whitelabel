<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CardPatternAssert;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that the Event content type renders correctly.
 */
class ContentEventRenderTest extends WhitelabelBrowserTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_starter_event',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set an explicit site timezone.
    $this->config('system.date')
      ->set('timezone.user.configurable', 1)
      ->set('timezone.default', 'UTC')
      ->save();
  }

  /**
   * Tests the event page.
   */
  public function testEventPage(): void {
    $node = $this->createExampleEvent();
    $this->drupalGet('node/' . $node->id());

    /** @var \Symfony\Component\BrowserKit\AbstractBrowser $client */
    $client = $this->getSession()->getDriver()->getClient();
    $crawler = $client->getCrawler();

    // Select the content banner element.
    $content_banner = $crawler->filter('.bcl-content-banner');
    $this->assertCount(1, $content_banner);

    // Assert content banner title.
    $this->assertEquals(
      'Test event node',
      trim($content_banner->filter('.card-title')->text())
    );

    // Assert content banner image.
    $image = $content_banner->filter('img');
    $this->assertCount(1, $image);
    $this->assertCount(1, $image->filter('.card-img-top'));
    $this->assertStringContainsString(
      'image-test.png',
      trim($image->attr('src'))
    );
    $this->assertEquals(
      'Starter Image test alt',
      $image->attr('alt')
    );

    // Assert content banner summary.
    $this->assertEquals(
      'https://www.example.org is a web page',
      trim($content_banner->filter('.oe-sc-event__oe-summary')->text())
    );

    // Assert registration button.
    $link = $crawler->filter('.bcl-content-banner a[target="_blank"]');
    $this->assertCount(1, $link);
    $this->assertEquals('https://europa.eu', $link->attr('href'));
    $this->assertStringContainsString('Register', $link->text());
    $this->assertStringContainsString('calendar-check', $link->html());

    // Assert registration button with internal route.
    $node->set('oe_sc_event_registration_url', 'entity:node/' . $node->id());
    $node->save();

    $this->drupalGet('node/' . $node->id());
    $crawler = $client->getCrawler();

    /** @var \Symfony\Component\DomCrawler\Crawler $link */
    $link = $crawler->filter('.bcl-content-banner a[href="/build/node/' . $node->id() . '"]');
    $this->assertCount(1, $link);
    $this->assertNull($link->attr('target'));
    $this->assertStringContainsString('Register', $link->text());
    $this->assertStringContainsString('calendar-check', $link->html());

    $date = $crawler->filter('dl dd');

    // Assert event dates starting and ending same day.
    $this->assertEquals('Wednesday 9 February 2022, 21.00-23.00 (CET)', trim($date->text()));

    // Assert event dates starting and ending at different days.
    $node->set('oe_sc_event_dates', [
      'value' => '2022-02-07T08:00:00',
      'end_value' => '2022-02-22T18:00:00',
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $crawler = $client->getCrawler();

    $date = $crawler->filter('dl:nth-of-type(1) dd');
    $this->assertEquals('Monday 7 February 2022, 09.00 (CET) - Tuesday 22 February 2022, 19.00 (CET)', trim($date->text()));

    // Assert address.
    $address = $crawler->filter('dl:nth-of-type(2) dd');
    $this->assertEquals('Charlemagne building, Wetstraat 170, 1040 Brussel, Belgium', trim($address->text()));

    // Assert in-page navigation title.
    $this->assertEquals(
      'Page content',
      trim($crawler->filter('nav.bcl-inpage-navigation > h2')->text())
    );

    // Assert in-page navigation links.
    $inpage_links = $crawler->filter('nav.bcl-inpage-navigation > ul');
    $this->assertCount(2, $inpage_links->filter('li'));
    $this->assertEquals(
      'Content',
      trim($inpage_links->filter('li:nth-of-type(1)')->text())
    );
    $this->assertEquals(
      'Documents',
      trim($inpage_links->filter('li:nth-of-type(2)')->text())
    );

    // Assert body text.
    $this->assertSame(
      'Event body',
      $crawler->filter('#oe-content-body p')->text()
    );

    // Assert inpage_navigation not loaded if there is no body and documents.
    $node->set('oe_documents', NULL);
    $node->set('body', NULL);
    $node->save();

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->elementNotExists('css', 'nav.bcl-inpage-navigation');
  }

  /**
   * Tests the event rendered in 'Teaser' view mode.
   */
  public function testEventRenderingTeaser(): void {
    $node = $this->createExampleEvent();
    // Build node teaser view.
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $expected = [
      'title' => 'Test event node',
      'description' => 'https://www.example.org is a web page',
      'content' => [
        '9 Feb 2022',
        'Brussel, Belgium',
      ],
      'date' => [
        'year' => '2022',
        'month' => 'Feb',
        'day' => '09',
        'date_time' => '2022-02-09',
      ],
    ];
    $card_assert = new CardPatternAssert();
    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern($expected, $html);

    // Assert event dates starting and ending at different days.
    $node->set('oe_sc_event_dates', [
      'value' => '2022-02-07T08:00:00',
      'end_value' => '2022-02-22T18:00:00',
    ]);
    $node->save();

    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $expected['content'] = [
      '7 Feb 2022 - 22 Feb 2022',
      'Brussel, Belgium',
    ];
    $expected['date'] = [
      'year' => '2022',
      'month' => 'Feb',
      'day' => '07',
      'end_year' => '2022',
      'end_month' => 'Feb',
      'end_day' => '22',
      'date_time' => '2022-02-07',
    ];
    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern($expected, $html);

    // Test timezone on event teaser.
    // Values stored are UTC.
    $node->set('oe_sc_event_dates', [
      'value' => '2024-06-07T00:00:00',
      'end_value' => '2024-06-07T23:59:59',
    ]);
    $node->save();

    // The site timezone is UTC so the event time is the same day.
    $expected['content'] = [
      '7 Jun 2024',
      'Brussel, Belgium',
    ];
    $expected['date'] = [
      'year' => '2024',
      'month' => 'Jun',
      'day' => '07',
      'end_year' => '2024',
      'date_time' => '2024-06-07',
    ];

    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern($expected, $html);

    // Check date rendering based on user timezone.
    // Set an explicit user timezone: UTC -5.
    $test_user = $this->createUser([], NULL, FALSE, ['timezone' => 'America/New_York']);
    $this->drupalLogin($test_user);

    // The user timezone is -5 so the event time be five hours earlier and will
    // start the day before.
    $expected['content'] = [
      '6 Jun 2024 - 7 Jun 2024',
      'Brussel, Belgium',
    ];
    $expected['date'] = [
      'year' => '2024',
      'month' => 'Jun',
      'day' => '06',
      'end_day' => '07',
      'end_month' => 'Jun',
      'end_year' => '2024',
      'date_time' => '2024-06-06',
    ];

    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern($expected, $html);
    $this->drupalLogout();

    // Check date rendering based on site timezone.
    // Set an explicit site timezone: UTC +2.
    $this->config('system.date')
      ->set('timezone.default', 'Europe/Madrid')
      ->save();

    // The site timezone is +2 so the event time be two hours later and will
    // last until the next day.
    $expected['content'] = [
      '7 Jun 2024 - 8 Jun 2024',
      'Brussel, Belgium',
    ];
    $expected['date'] = [
      'year' => '2024',
      'month' => 'Jun',
      'day' => '07',
      'end_day' => '08',
      'end_month' => 'Jun',
      'end_year' => '2024',
      'date_time' => '2024-06-07',
    ];

    $build = $builder->view($node, 'teaser');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    $card_assert->assertVariant('search', $html);
    $card_assert->assertPattern($expected, $html);
  }

  /**
   * Creates an example event node.
   *
   * @return \Drupal\node\NodeInterface
   *   Event node.
   */
  protected function createExampleEvent(): NodeInterface {
    // Create a sample media entity to be embedded.
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $media_image = Media::create([
      'bundle' => 'image',
      'name' => 'Starter Image test',
      'oe_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'Starter Image test alt',
          'title' => 'Starter Image test title',
        ],
      ],
    ]);
    $media_image->save();

    // Create a sample document media entity to be embedded.
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    $media_document = Media::create([
      'bundle' => 'document',
      'name' => 'Event document test',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        [
          'target_id' => 2,
          'alt' => 'Event document alt',
          'title' => 'Event document title',
        ],
      ],
    ]);
    $media_document->save();

    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'oe_sc_event',
        'title' => 'Test event node',
        'oe_summary' => 'https://www.example.org is a web page',
        'body' => 'Event body',
        'oe_sc_event_dates' => [
          'value' => '2022-02-09T20:00:00',
          'end_value' => '2022-02-09T22:00:00',
        ],
        'oe_sc_event_location' => [
          'country_code' => 'BE',
          'address_line1' => 'Charlemagne building, Wetstraat 170',
          'postal_code' => '1040',
          'locality' => 'Brussel',
        ],
        'uid' => 1,
        'status' => 1,
      ]);
    $node->set('oe_documents', [$media_document]);
    $node->set('oe_featured_media', [$media_image]);
    $node->set('oe_sc_event_registration_url', 'https://europa.eu');
    $node->save();
    return $node;
  }

}
