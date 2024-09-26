<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_agenda\Kernel;

use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\IconPatternAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\LinkPatternAssert;
use Drupal\Tests\oe_whitelabel\Kernel\AbstractKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\oe_agenda\Entity\Agenda;
use Drupal\oe_agenda\Entity\AgendaInterface;
use Drupal\oe_agenda\Entity\Day as AgendaDay;
use Drupal\oe_agenda\Entity\Session as AgendaSession;
use Drupal\oe_agenda\Entity\SessionInterface;
use Drupal\oe_content_sub_entity_person\Entity\Person;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests that the agenda renders with the correct markup.
 */
class AgendaRenderTest extends AbstractKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // Schema checker fails when trying to import the config.
    // See: https://github.com/openeuropa/oe_content/issues/634
    // @todo remove when fixed in oe_content.
    'core.entity_view_display.oe_agenda_session.oe_default.default',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'composite_reference',
    'datetime',
    'entity_reference_revisions',
    'field',
    'filter',
    'inline_entity_form',
    'link',
    'link_description',
    'node',
    'oe_content_entity',
    'oe_content_sub_entity',
    'oe_content_sub_entity_person',
    'oe_agenda',
    'oe_agenda_test',
    'oe_whitelabel_agenda',
    'oe_whitelabel_agenda_test',
    'ui_patterns',
    'system',
    'text',
    'twig_field_value',
    'time_field',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('oe_agenda');
    $this->installEntitySchema('oe_agenda_day');
    $this->installEntitySchema('oe_agenda_session');
    $this->installEntitySchema('oe_person');
    $this->installConfig([
      'filter',
      'node',
      'oe_agenda',
      'oe_agenda_test',
      'oe_whitelabel_agenda',
      'oe_content_sub_entity_person',
    ]);

    \Drupal::moduleHandler()->loadInclude('oe_whitelabel_agenda', 'install');
    oe_whitelabel_agenda_install(FALSE);
    \Drupal::moduleHandler()->loadInclude('oe_whitelabel_agenda_test', 'install');
    oe_whitelabel_agenda_test_install(FALSE);
  }

  /**
   * Tests that agenda is rendered with the correct markup.
   */
  public function testRendering(): void {
    $render = \Drupal::entityTypeManager()
      ->getViewBuilder('oe_agenda')
      ->view($this->createAgenda());
    $html = $this->renderRoot($render);
    $crawler = new Crawler($html);

    // Assert agenda wrapper.
    $agenda = $crawler->filter('.oe-agenda.accordion');
    $this->assertCount(1, $agenda);

    // Assert number of agenda days.
    $days = $agenda->children('.accordion-item');
    $this->assertCount(2, $days);
    // Assert all agenda days are collapsed.
    $this->assertCount(2, $crawler->filter('.accordion-button.collapsed'));

    // Assert Day 1 date is displayed in the correct format.
    $wrapper = $crawler->filter('#heading-bcl-accordion-1 .accordion-button time');
    $this->assertEquals('Thursday - 26 September', $wrapper->text());

    // Assert Day 2 date is displayed in the correct format.
    $wrapper = $crawler->filter('#heading-bcl-accordion-2 .accordion-button time');
    $this->assertEquals('Friday - 27 September', $wrapper->text());

    // Assert Day 2 title is displayed.
    $wrapper = $days->eq(1)->filter('.accordion-body > h3');
    $this->assertEquals('Day 2 Title', $wrapper->text());

    // Assert number of sessions on Day 1.
    $sessions = $days->eq(0)->filter('.accordion-body > div');
    $this->assertCount(3, $sessions);

    // Assert Session 1 layout.
    $session_left_col_selector = '.row .col-xxl-2.col-xl-3.col-lg-3.mb-3.mb-lg-0';
    $session_left_col = $sessions->eq(0)->filter($session_left_col_selector);
    $this->assertCount(1, $session_left_col);
    $session_right_col_selector = '.row .session-content.col-xxl-10.col-xl-9.col-lg-9';
    $session_right_col = $sessions->eq(0)->filter($session_right_col_selector);
    $this->assertCount(1, $session_right_col);

    // Assert Day 1 Session 1 icon and time are displayed.
    $this->assertSessionHours([
      'from' => '9:00',
      'to' => '10:30',
    ], $session_left_col);

    // Assert Day 1 Session 1 title is displayed.
    $wrapper = $session_right_col->filter('h4.h5:first-child');
    $this->assertEquals('Session 1 Title', $wrapper->text());

    // Select Day 1 Session 1 section wrappers.
    $session_sections = $session_right_col->children('div.mb-4-5');

    // Assert Day 1 Session 1 intro is displayed.
    $this->assertEquals('Session 1 introduction.', $session_sections->eq(0)->text());

    // Assert Day 1 Session 1 moderators are displayed.
    $this->assertEquals('Moderators', $session_sections->eq(1)->filter('h5.mb-4')->text());
    $this->assertEquals('Phasellus Viverra', $session_sections->eq(1)->filter('div.mb-2 a')->text());
    $this->assertEquals('Sed Hendrerit', $session_sections->eq(1)->filter('div:last-child a')->text());

    // Assert Day 1 Session 1 speakers are displayed.
    $this->assertEquals('Speakers', $session_sections->eq(2)->filter('h5.mb-4')->text());
    $this->assertEquals('Pellentesque Habitant', $session_sections->eq(2)->filter('div.mb-3 a')->text());
    $this->assertEquals('Vestibulum Curabitur', $session_sections->eq(2)->filter('div:last-child a')->text());

    // Assert Day 1 Session 1 venue and online link are displayed.
    $description_list = $session_sections->eq(3)->filter('.bcl-description-list');
    $link_wrappers = $description_list->filter('dl.mb-3.row');
    $this->assertEquals('Venue:', $link_wrappers->eq(0)->filter('.col-md-3 dt')->text());
    $venue_details = $link_wrappers->eq(0)->filter('.col dd div');
    $link_assert = new LinkPatternAssert();
    $link_assert->assertPattern([
      'label' => 'Venue link text',
      'path' => '/node/1',
    ], $venue_details->children('a')->outerHtml());
    $this->assertEquals('Venue description', $venue_details->children('p')->text());
    $this->assertEquals('Online link:', $link_wrappers->eq(1)->filter('.col-md-3 dt')->text());
    $online_link_details = $link_wrappers->eq(1)->filter('.col dd div');
    $online_link = $online_link_details->children('a');
    $link_assert->assertPattern([
      'label' => 'Online link text',
      'path' => 'https://example.com',
      'settings' => [
        'icon_position' => 'after',
      ],
      'attributes' => [
        'target' => '_blank',
      ],
    ], $online_link->outerHtml());
    // Can't use LinkPatternAssert for the icon, as it hard-codes the icon size.
    $icon_assert = new IconPatternAssert();
    $icon_assert->assertPattern([
      'name' => 'box-arrow-up-right',
      'size' => 'xs',
    ], $online_link->children('svg')->outerHtml());
    $this->assertEquals('Online link description', $online_link_details->children('p')->text());

    // Assert Day 1 Session 1 details section is displayed.
    $this->assertEquals('Session 1 details.', $session_right_col->children('.session-details')->text());

    // Assert Day 1 Session 2 (Break) layout.
    $session_2_left_col = $sessions->eq(1)->filter($session_left_col_selector);
    $this->assertCount(1, $session_2_left_col);
    $session_2_right_col = $sessions->eq(1)->filter($session_right_col_selector);
    $this->assertCount(1, $session_2_right_col);

    // Assert Day 1 Session 2 (Break) icon and time are displayed.
    $this->assertSessionHours([
      'session_type' => 'oe_break',
      'from' => '10:30',
      'to' => '11:00',
    ], $session_2_left_col);

    // Assert Day 1 Session 2 (Break) title is displayed.
    $wrapper = $session_2_right_col->filter('h4.h5.text-muted:first-child');
    $this->assertEquals('Break', $wrapper->text());

    // Assert Day 1 Session 2 (Break) details section is displayed.
    $this->assertEquals('Session 2 (Break) details.', $session_2_right_col->children('.session-details')->text());

    // Assert Day 2 Session 2 (Break) custom title is displayed.
    $day_2_sessions = $days->eq(1)->filter('.accordion-body > div');
    $day_2_session_2_right_col = $day_2_sessions->eq(1)->filter($session_right_col_selector);
    $wrapper = $day_2_session_2_right_col->filter('h4.h5.text-muted:first-child');
    $this->assertEquals('Break Custom Title', $wrapper->text());
  }

  /**
   * Asserts the session hours.
   *
   * @param array $expected
   *   The expected settings.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The crawler.
   */
  protected function assertSessionHours(array $expected, Crawler $crawler): void {
    $session_type = $expected['session_type'] ?? 'oe_default';
    $from = $expected['from'] ?? '';
    $to = $expected['to'] ?? '';

    $icon_assert = new IconPatternAssert();
    $icon = $crawler->children('svg');
    $this->assertCount(1, $icon);
    $icon_assert->assertPattern([
      'name' => 'clock',
      'size' => 'xs',
      'attributes' => [
        'class' => 'mb-1 me-2 bi icon--xs',
      ],
    ], $icon->outerHtml());
    $time_wrapper_selector = $session_type === 'oe_break' ? 'span.text-muted' : 'span';
    $this->assertEquals("{$from} - {$to}", $crawler->children($time_wrapper_selector)->text());
  }

  /**
   * Creates an agenda using data for testing.
   *
   * @return \Drupal\oe_agenda\Entity\AgendaInterface
   *   The created test agenda.
   */
  protected function createAgenda(): AgendaInterface {
    $days = [
      [
        'date' => '2024-09-26',
        'sessions' => [
          [
            'hours' => [
              'from' => 32400,
              'to' => 37800,
            ],
            'name' => 'Session 1 Title',
            'intro' => 'Session 1 introduction.',
            'moderators' => $this->createPersons([
              'Phasellus Viverra',
              'Sed Hendrerit',
            ]),
            'speakers' => $this->createPersons([
              'Pellentesque Habitant',
              'Vestibulum Curabitur',
            ]),
            'venue' => [
              'uri' => 'entity:node/1',
              'title' => 'Venue link text',
              'description' => 'Venue description',
            ],
            'online_link' => [
              'uri' => 'https://example.com',
              'title' => 'Online link text',
              'description' => 'Online link description',
            ],
            'details' => 'Session 1 details.',
          ],
          [
            'type' => 'oe_break',
            'hours' => [
              'from' => 37800,
              'to' => 39600,
            ],
            'details' => 'Session 2 (Break) details.',
          ],
          [
            'hours' => [
              'from' => 39600,
              'to' => 43200,
            ],
          ],
        ],
      ],
      [
        'date' => '2024-09-27',
        'title' => 'Day 2 Title',
        'sessions' => [
          [
            'hours' => [
              'from' => 30600,
              'to' => 34200,
            ],
          ],
          [
            'type' => 'oe_break',
            'name' => 'Break Custom Title',
            'hours' => [
              'from' => 34200,
              'to' => 35100,
            ],
          ],
        ],
      ],
    ];

    $agenda_days = [];
    foreach ($days as $day) {
      $agenda_sessions = [];
      foreach ($day['sessions'] as $session_data) {
        $agenda_sessions[] = $this->createAgendaSession($session_data);
      }

      $agenda_day = AgendaDay::create([
        'type' => 'oe_default',
        'oe_day_date' => $day['date'],
        'oe_day_sessions' => $agenda_sessions,
      ]);
      if (!empty($day['title'])) {
        $agenda_day->set('title', $day['title']);
      }
      $agenda_day->save();
      $agenda_days[] = $agenda_day;
    }

    $agenda = Agenda::create([
      'type' => 'oe_default',
      'oe_agenda_days' => $agenda_days,
    ]);
    $agenda->save();

    return $agenda;
  }

  /**
   * Creates an agenda session using data for testing.
   *
   * @return \Drupal\oe_agenda\Entity\SessionInterface
   *   The created test agenda session.
   */
  protected function createAgendaSession(array $session_data): SessionInterface {
    $session_type = $session_data['type'] ?? 'oe_default';
    $from = $session_data['hours']['from'] ?? 0;
    $to = $session_data['hours']['to'] ?? 3600;
    $session = AgendaSession::create([
      'type' => $session_type,
      'oe_session_hours' => [
        'from' => $from,
        'to' => $to,
      ],
    ]);
    if (!empty($session_data['name'])) {
      $session->set('name', $session_data['name']);
    }
    if (!empty($session_data['intro'])) {
      $session->set('oe_session_intro', $session_data['intro']);
    }
    if (!empty($session_data['moderators'])) {
      $session->set('oe_session_moderators', $session_data['moderators']);
    }
    if (!empty($session_data['speakers'])) {
      $session->set('oe_session_speakers', $session_data['speakers']);
    }
    if (!empty($session_data['venue'])) {
      $session->set('oe_session_venue', $session_data['venue']);
    }
    if (!empty($session_data['online_link'])) {
      $session->set('oe_session_online_link', $session_data['online_link']);
    }
    if (!empty($session_data['details'])) {
      $session->set('oe_session_details', $session_data['details']);
    }
    $session->save();

    return $session;
  }

  /**
   * Creates nodes and referencing person subentities from a list of names.
   *
   * @param string[] $names
   *   An array of names to be used as names of the persons.
   *
   * @return \Drupal\oe_content_sub_entity_person\Entity\PersonInterface[]
   *   An array of person subentities to be referenced by certain agenda fields.
   */
  protected function createPersons(array $names): array {
    $persons = [];
    foreach ($names as $name) {
      $node = Node::create([
        'type' => 'test_person_bundle',
        'title' => $name,
      ]);
      $persons[] = Person::create([
        'type' => 'test_person_type',
        'oe_test_person_reference' => $node,
      ]);
    }

    return $persons;
  }

}
