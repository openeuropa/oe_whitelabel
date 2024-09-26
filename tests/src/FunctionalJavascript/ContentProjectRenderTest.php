<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\ContentBannerAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\DescriptionListAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\GalleryPatternAssert;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\InPageNavigationAssert;
use Drupal\Tests\oe_whitelabel\Traits\NodeCreationTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\node\NodeInterface;
use Drupal\oe_content_entity\Entity\CorporateEntityInterface;
use Drupal\oe_content_entity_organisation\Entity\OrganisationInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests that our Project content type renders correctly.
 */
class ContentProjectRenderTest extends WebDriverTestBase {

  use NodeCreationTrait;
  use SparqlConnectionTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_media_oembed_mock',
    'oe_whitelabel_extra_project',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();

    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view published skos concept entities')
      ->grantPermission('view media')
      ->grantPermission('view published oe_organisation')
      ->save();

    $this->config('system.date')
      ->set('timezone.default', 'Europe/Brussels')
      ->save();
  }

  /**
   * Tests that the Project page renders correctly.
   */
  public function testProjectRendering(): void {
    $assert_session = $this->assertSession();
    $image = $this->createImageMedia([
      'oe_media_image' => [
        'alt' => 'Image test alt',
        'title' => 'Image test title',
      ],
    ]);
    // Create organisations for Coordinators and Participants fields.
    // Unpublished entity should not be shown.
    $coordinator_organisation = $this->createStakeholderOrganisationEntity('coordinator', CorporateEntityInterface::PUBLISHED, 'oe_stakeholder');
    $participant_organisation = $this->createStakeholderOrganisationEntity('participant', CorporateEntityInterface::PUBLISHED, 'oe_cx_project_stakeholder');

    // Create medias for gallery.
    $gallery_image = $this->createImageMedia();
    $gallery_video = $this->createRemoteVideoMedia();
    $gallery_av_photo = $this->createAvPortalPhotoMedia();
    $gallery_av_video = $this->createAvPortalVideoMedia();

    // Create a Project node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getStorage('node')->create([
      'type' => 'oe_project',
      'title' => 'Test project node',
      'oe_teaser' => 'Test project node',
      'oe_summary' => 'Summary',
      'oe_featured_media' => [
        'target_id' => $image->id(),
        'caption' => 'Caption project_featured_media',
      ],
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2025-05-15',
      ],
      'oe_project_budget' => '200',
      'oe_project_budget_eu' => '70',
      'oe_project_website' => [
        [
          'uri' => 'http://example.com',
          'title' => 'Example website',
        ],
      ],
      'oe_reference_code' => 'Project reference',
      'oe_subject' => 'http://data.europa.eu/uxp/1386',
      'oe_project_funding_programme' => 'http://publications.europa.eu/resource/authority/eu-programme/AFIS2020',
      'oe_project_coordinators' => [$coordinator_organisation],
      'oe_project_participants' => [$participant_organisation],
      'oe_cx_objective' => 'Objective',
      'oe_cx_impacts' => 'Impacts',
      'oe_cx_achievements_and_milestone' => 'Achievements and milestone',
      'oe_cx_gallery' => [
        $gallery_image->id(),
        $gallery_video->id(),
        $gallery_av_photo->id(),
        $gallery_av_video->id(),
      ],
      'uid' => 0,
      'status' => 1,
    ]);
    $node->save();
    $this->drupalGet($node->toUrl());

    // Assert content banner.
    $content_banner = $assert_session->elementExists('css', '.bcl-content-banner');
    $assert = new ContentBannerAssert();
    $assert->assertPattern([
      'image' => [
        'alt' => 'Image test alt',
        'src' => 'example_1.jpeg',
      ],
      'badges' => ['wood industry'],
      'title' => 'Test project node',
      'content' => 'Test project node',
    ], $content_banner->getOuterHtml());

    // Assert in-page navigation.
    $inpage_nav = $this->assertSession()->elementExists('css', 'nav.bcl-inpage-navigation');
    $inpage_nav_assert = new InPageNavigationAssert();
    $inpage_nav_assert->assertPattern([
      'title' => 'Page content',
      'links' => [
        [
          'label' => 'Project details',
          'href' => '#project-details',
        ],
        [
          'label' => 'Summary',
          'href' => '#oe-project-oe-summary',
        ],
        [
          'label' => 'Objective',
          'href' => '#oe-project-oe-cx-objective',
        ],
        [
          'label' => 'Impacts',
          'href' => '#oe-project-oe-cx-impacts',
        ],
        [
          'label' => 'Participants',
          'href' => '#oe-project-oe-project-participants',
        ],
        [
          'label' => 'Achievements and milestones',
          'href' => '#oe-project-oe-cx-achievements-and-milestone',
        ],
        [
          'label' => 'Gallery',
          'href' => '#oe-project-oe-cx-gallery',
        ],
      ],
    ], $inpage_nav->getOuterHtml());

    // Select the content column next to the in-page navigation.
    $project_content = $assert_session->elementExists('css', '.col-md-9');

    $this->assertProjectDates('10 May 2020', '15 May 2025');
    $this->assertProjectStatusTimestampsAsDateStrings('2020-05-10 00:00:00', '2025-05-16 00:00:00');
    $this->assertProjectStatusVisible();

    $contributions_chart = $assert_session->elementExists('css', '.bcl-project-contributions .circular-progress');
    // The correct value would be 35, but it is rounded up to 40, because no
    // utility classes are available for smaller increments.
    $this->assertSame('40', $contributions_chart->getAttribute('data-percentage'));

    // Select the description blocks inside the Project details.
    $description_lists = $project_content->findAll('css', '.bcl-description-list');
    $this->assertCount(4, $description_lists);

    $description_list_assert = new DescriptionListAssert();

    // Assert budget list group.
    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Overall budget',
          'definition' => '€200,00',
        ],
        [
          'term' => 'EU contribution',
          'definition' => '€70,00',
        ],
      ],
    ], $description_lists[0]->getOuterHtml());

    // Assert details list group.
    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Website',
          'definition' => 'Example website',
        ],
        [
          'term' => 'Funding programme',
          'definition' => 'Anti Fraud Information System (AFIS) (2014/2020)',
        ],
        [
          'term' => 'Reference',
          'definition' => 'Project reference',
        ],
      ],
    ], $description_lists[1]->getOuterHtml());

    // Assert coordinators list group.
    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Coordinators',
          'definition' => 'coordinator',
        ],
      ],
    ], $description_lists[2]->getOuterHtml());

    // Assert participants list group.
    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Name',
          'definition' => 'participant',
        ],
        [
          'term' => 'Address',
          'definition' => 'Belgium',
        ],
        [
          'term' => 'Contribution to the budget',
          'definition' => '€22,30',
        ],
      ],
    ], $description_lists[3]->getOuterHtml());

    // Set a project period that is fully in the past.
    $this->setProjectDateRange($node, '2019-03-07', '2019-03-21');
    $node->save();
    $this->drupalGet($node->toUrl());

    $this->assertProjectDates('07 March 2019', '21 March 2019');
    $this->assertProjectStatusTimestampsAsDateStrings('2019-03-07 00:00:00', '2019-03-22 00:00:00');
    $this->assertProjectStatusVisible();
    $this->assertProjectStatus('bg-dark', 'Closed');
    $this->assertProjectProgress(100);

    // Set a project period that is ongoing.
    $this->setProjectDateRange($node, '-5 day', '+15 days');
    $node->save();
    $this->drupalGet($node->toUrl());

    $this->assertProjectStatusVisible();
    $this->assertProjectStatus('bg-info', 'Ongoing');
    $this->assertProjectProgress(15, 35);

    // Set a project period that is fully in the future.
    $this->setProjectDateRange($node, '+5 days', '+12 days');
    $node->save();
    $this->drupalGet($node->toUrl());

    $this->assertProjectStatusVisible();
    $this->assertProjectStatus('bg-secondary', 'Planned');
    $this->assertProjectProgress(0);

    // Assert budget with new fields.
    $node->set('oe_project_eu_budget', '104479592');
    $node->set('oe_project_eu_contrib', '7812356');
    $node->save();
    $this->drupalGet($node->toUrl());
    $description_list = $project_content->find('css', '.bcl-description-list');

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Overall budget',
          'definition' => '€104.479.592,00',
        ],
        [
          'term' => 'EU contribution',
          'definition' => '€7.812.356,00',
        ],
      ],
    ], $description_list->getOuterHtml());

    $file_url_generator = \Drupal::service('file_url_generator');
    $gallery_container = $assert_session->elementExists('css', '#oe-project-oe-cx-gallery + .bcl-gallery');
    $fn_get_filepath = static fn($entity, $field) => $file_url_generator->generate($entity->get($field)->entity->getFileUri())->toString();
    (new GalleryPatternAssert())->assertPattern([
      'title' => NULL,
      'items' => [
        [
          'thumbnail' => [
            'caption_title' => 'Image title',
            'rendered' => sprintf(
              '<img loading="lazy" src="%s" width="200" height="89" alt="Alt text" class="img-fluid">',
              $fn_get_filepath($gallery_image, 'oe_media_image')
            ),
          ],
          'media' => [
            'caption_title' => 'Image title',
            'rendered' => sprintf(
              '<img loading="lazy" data-src="%s" width="200" height="89" alt="Alt text" class="img-fluid">',
              $fn_get_filepath($gallery_image, 'oe_media_image')
            ),
          ],
        ],
        [
          'thumbnail' => [
            'caption_title' => 'Energy, let\'s save it!',
            'rendered' => sprintf(
              '<img loading="lazy" src="%s" width="480" height="360" alt="" class="img-fluid">',
              $fn_get_filepath($gallery_video, 'thumbnail')
            ),
            'play_icon' => TRUE,
          ],
          'media' => [
            'caption_title' => 'Energy, let\'s save it!',
            'rendered' => sprintf(
              '<iframe data-src="%s?url=https%%3A//www.youtube.com/watch%%3Fv%%3D1-g73ty9v04&amp;max_width=0&amp;max_height=0&amp;hash=%s"%s width="459" height="344" class="media-oembed-content" loading="eager" title="Energy, let\'s save it!"></iframe>',
              Url::fromRoute('media.oembed_iframe')->setAbsolute()->toString(),
              \Drupal::service('media.oembed.iframe_url_helper')->getHash('https://www.youtube.com/watch?v=1-g73ty9v04', 0, 0),
              // @todo Remove when support for 10.2.x is dropped.
              version_compare(\Drupal::VERSION, '10.3', '<') ? ' frameborder="0" allowtransparency=""' : '',
            ),
          ],
        ],
        [
          'thumbnail' => [
            'caption_title' => 'Euro with miniature figurines',
            'rendered' => sprintf(
              '<img loading="lazy" src="%s" width="639" height="426" alt="Euro with miniature figurines" class="img-fluid">',
              $fn_get_filepath($gallery_av_photo, 'thumbnail')
            ),
          ],
          'media' => [
            'caption_title' => 'Euro with miniature figurines',
            'rendered' => '<img class="avportal-photo img-fluid" alt="Euro with miniature figurines" data-src="https://ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg">',
          ],
        ],
        [
          'thumbnail' => [
            'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
            'rendered' => sprintf(
              '<img loading="lazy" src="%s" width="352" height="200" alt="" class="img-fluid">',
              $fn_get_filepath($gallery_av_video, 'thumbnail')
            ),
            'play_icon' => TRUE,
          ],
          'media' => [
            'caption_title' => 'Economic and Financial Affairs Council - Arrivals',
            'rendered' => '<iframe id="videoplayerI-163162" data-src="https://ec.europa.eu/avservices/play.cfm?ref=I-163162&amp;lg=EN&amp;sublg=none&amp;autoplay=true&amp;tin=10&amp;tout=59" frameborder="0" allowtransparency="" allowfullscreen="" webkitallowfullscreen="" mozallowfullscreen="" width="640" height="390" class="media-avportal-content"></iframe>',
          ],
        ],
      ],
    ], $gallery_container->getOuterHtml());
  }

  /**
   * Tests the status badge rendering for the teaser view mode.
   */
  public function testTeaserStatusBadge(): void {
    // Create a project with dates set in the past.
    $this->createProjectNode([
      'oe_project_dates' => [
        'value' => '2000-05-10',
        'end_value' => '2010-05-15',
      ],
    ]);

    // In order to render a teaser, we use the default node list view.
    $this->drupalGet('/node');

    $teasers = $this->getSession()->getPage()->findAll('css', 'article.listing-item');
    $this->assertCount(1, $teasers);
    $this->assertTeaserStatusBadge($teasers[0], 'Closed', 'bg-dark');

    // Create an ongoing project.
    $this->createProjectNode([
      'oe_project_dates' => [
        'value' => '2010-05-10',
        'end_value' => '2100-05-15',
      ],
    ]);
    // And a planned project.
    $this->createProjectNode([
      'oe_project_dates' => [
        'value' => '2100-05-10',
        'end_value' => '2200-05-15',
      ],
    ]);

    $this->drupalGet('/node');
    $teasers = $this->getSession()->getPage()->findAll('css', 'article.listing-item');
    $this->assertCount(3, $teasers);
    $this->assertTeaserStatusBadge($teasers[0], 'Planned', 'bg-secondary');
    $this->assertTeaserStatusBadge($teasers[1], 'Ongoing', 'bg-info');
    $this->assertTeaserStatusBadge($teasers[2], 'Closed', 'bg-dark');
  }

  /**
   * Creates a stakeholder organisation entity.
   *
   * @param string $name
   *   Name of the entity. Is used as a parameter for test data.
   * @param int $status
   *   Entity status. 1 - published, 0 - unpublished.
   * @param string $bundle
   *   Bundle name used in the entity.
   *
   * @return \Drupal\oe_content_entity_organisation\Entity\OrganisationInterface
   *   Organisation entity.
   */
  protected function createStakeholderOrganisationEntity(string $name, int $status, string $bundle): OrganisationInterface {
    $organisation = $this->getStorage('oe_organisation')->create([
      'bundle' => $bundle,
      'name' => $name,
      'oe_acronym' => "Acronym $name",
      'oe_address' => [
        'country_code' => 'BE',
      ],
      'oe_cx_contribution_budget' => '22.3',
      'status' => $status,
    ]);
    $organisation->save();

    return $organisation;
  }

  /**
   * Gets the entity type's storage.
   *
   * @param string $entity_type_id
   *   The entity type ID to get a storage for.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity type's storage.
   */
  protected function getStorage(string $entity_type_id): EntityStorageInterface {
    return \Drupal::entityTypeManager()->getStorage($entity_type_id);
  }

  /**
   * Updates the project date, saves the node, and refreshes the page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to update.
   * @param string $begin
   *   Start date string.
   * @param string $end
   *   End date string.
   */
  protected function setProjectDateRange(NodeInterface $node, string $begin, string $end): void {
    $node->set('oe_project_dates', [
      [
        'value' => (new DrupalDateTime($begin, 'Europe/Brussels'))->format('Y-m-d'),
        'end_value' => (new DrupalDateTime($end, 'Europe/Brussels'))->format('Y-m-d'),
      ],
    ]);
  }

  /**
   * Asserts that the d-none class has been removed from project status.
   */
  protected function assertProjectStatusVisible(): void {
    $status_section = $this->assertSession()->elementExists('css', '.bcl-project-status');
    $this->assertFalse($status_section->hasClass('d-none'));
  }

  /**
   * Asserts 'start' and 'end' timestamp in project status section.
   *
   * @param string $begin
   *   Expected start date string as 'Y-m-d H:i:s'.
   * @param string $end
   *   Expected end date string as 'Y-m-d H:i:s'.
   */
  protected function assertProjectStatusTimestampsAsDateStrings(string $begin, string $end): void {
    $status_section = $this->assertSession()->elementExists('css', '.bcl-project-status');
    $t_begin = (int) $status_section->getAttribute('data-start-timestamp');
    $t_end = (int) $status_section->getAttribute('data-end-timestamp');
    $this->assertTimestampAsDateString($begin, $t_begin, 'Europe/Brussels');
    $this->assertTimestampAsDateString($end, $t_end, 'Europe/Brussels');
  }

  /**
   * Asserts a timestamp matches a date string in a given timezone.
   *
   * @param string $expected
   *   Expected date string as 'Y-m-d H:i:s'.
   * @param int $timestamp
   *   Actual timestamp.
   * @param string $timezone
   *   Timezone for the conversion.
   */
  protected function assertTimestampAsDateString(string $expected, int $timestamp, string $timezone): void {
    $this->assertSame(
      $expected,
      DrupalDateTime::createFromTimestamp($timestamp, $timezone)
        ->format('Y-m-d H:i:s'),
    );
  }

  /**
   * Asserts the state of the status badge and the color of the progress bar.
   *
   * @param string $color_class
   *   Expected color class.
   * @param string $status_text
   *   Expected status text.
   */
  protected function assertProjectStatus(string $color_class, string $status_text): void {
    $status_badge = $this->assertSession()->elementExists('css', '.bcl-project-status .badge');
    $this->assertTrue($status_badge->hasClass($color_class));
    $this->assertSame($status_text, $status_badge->getHtml());
    $progress_bar = $this->assertSession()->elementExists('css', '.bcl-project-status .progress-bar');
    $this->assertTrue($progress_bar->hasClass($color_class));
  }

  /**
   * Asserts the value of the progress bar.
   *
   * @param int $min
   *   Minimum progress in percent.
   * @param int|null $max
   *   Maximum progress in percent.
   */
  protected function assertProjectProgress(int $min, ?int $max = NULL): void {
    $progress_bar = $this->assertSession()->elementExists('css', '.bcl-project-status .progress-bar');
    $progress_string = $progress_bar->getAttribute('aria-valuenow');
    $this->assertStringContainsString("width: $progress_string%", $progress_bar->getAttribute('style'));
    if ($max === NULL) {
      $this->assertSame((string) $min, $progress_string);
    }
    else {
      $this->assertGreaterThanOrEqual($min, (float) $progress_string);
      $this->assertLessThanOrEqual($max, (float) $progress_string);
    }
  }

  /**
   * Assert the rendered dates in the project status area.
   *
   * @param string $expected_start_date
   *   The expected start date string.
   * @param string $expected_end_date
   *   The expected end date string.
   */
  protected function assertProjectDates(string $expected_start_date, string $expected_end_date): void {
    $wrapper = $this->assertSession()->elementExists('css', '.bcl-project-status');
    $start_element = $this->assertSession()->elementExists('xpath', '//p[contains(text(), "Start")]//time', $wrapper);
    $this->assertEquals($expected_start_date, trim($start_element->getText()));
    $end_element = $this->assertSession()->elementExists('xpath', '//p[contains(text(), "End")]//time', $wrapper);
    $this->assertEquals($expected_end_date, trim($end_element->getText()));
  }

  /**
   * Asserts the teaser status badge.
   *
   * @param \Behat\Mink\Element\NodeElement $wrapper
   *   The teaser wrapper element.
   * @param string $status_label
   *   The expected status label.
   * @param string $status_class
   *   The expected status class.
   */
  protected function assertTeaserStatusBadge(NodeElement $wrapper, string $status_label, string $status_class): void {
    $badges = $wrapper->findAll('css', '.badge');
    $this->assertCount(2, $badges);
    $this->assertEquals($status_label, $badges[0]->getText());
    $this->assertTrue($badges[0]->hasClass($status_class));
  }

}
