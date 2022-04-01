<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\oe_content_entity\Entity\CorporateEntityInterface;
use Drupal\oe_content_entity_organisation\Entity\OrganisationInterface;
use Drupal\Tests\oe_whitelabel\PatternAssertions\InPageNavigationAssert;
use Drupal\Tests\oe_whitelabel\PatternAssertions\DescriptionListAssert;
use Drupal\Tests\oe_whitelabel\PatternAssertions\ContentBannerAssert;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that our Project content type renders correctly.
 */
class ContentProjectRenderTest extends WhitelabelBrowserTestBase {

  use SparqlConnectionTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_whitelabel_extra_project',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();

    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Tests that the Project page renders correctly.
   */
  public function testProjectRendering(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create a media entity.
    // Create file and media.
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Image test',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'Image test alt',
          'title' => 'Image test title',
        ],
      ],
    ]);
    $media->save();
    // Create organisations for Coordinators and Participants fields.
    // Unpublished entity should not be shown.
    $coordinator_organisation = $this->createStakeholderOrganisationEntity('coordinator', CorporateEntityInterface::PUBLISHED, 'oe_stakeholder');
    $participant_organisation = $this->createStakeholderOrganisationEntity('participant', CorporateEntityInterface::PUBLISHED, 'oe_cx_project_stakeholder');

    // Create a Project node.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getStorage('node')->create([
      'type' => 'oe_project',
      'title' => 'Test project node',
      'oe_teaser' => 'Test project node',
      'oe_summary' => 'Summary',
      'oe_featured_media' => [
        'target_id' => (int) $media->id(),
        'caption' => 'Caption project_featured_media',
      ],
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2025-05-15',
      ],
      'oe_project_budget' => '100',
      'oe_project_budget_eu' => '100',
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
      'uid' => 0,
      'status' => 1,
    ]);
    $node->save();
    $this->drupalGet($node->toUrl());

    // Assert page header - metadata.
    $page_header = $assert_session->elementExists('css', '.bcl-content-banner');
    $assert = new ContentBannerAssert();
    $expected_values = [
      'image' => [
        'alt' => 'Image test alt',
        'src' => 'image-test.png',
      ],
      'badges' => ['wood industry'],
      'title' => 'Test project node',
      'description' => 'Test project node',
    ];
    $assert->assertPattern($expected_values, $page_header->getOuterHtml());

    // Assert navigation.
    $navigation = $this->assertSession()->elementExists('css', 'nav.bcl-inpage-navigation');
    $inpage_nav_assert = new InPageNavigationAssert();
    $inpage_nav_expected_values = [
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
      ],
    ];
    $inpage_nav_assert->assertPattern($inpage_nav_expected_values, $navigation->getOuterHtml());

    // Assert top region - Project details.
    $project_data = $assert_session->elementExists('css', '.col-md-9');

    // Assert the description blocks inside the Project details.
    $description_lists = $project_data->findAll('css', '.grid-3-9');
    $this->assertCount(5, $description_lists);

    // Period list group.
    $field_list_assert = new DescriptionListAssert();
    $first_field_list_expected_values = [
      'items' => [
        [
          'term' => 'Project period',
          'definition' => "10 May 2020\n - 15 May 2025",
        ],
      ],
    ];
    $field_list_html = $description_lists[0]->getHtml();
    $field_list_assert->assertPattern($first_field_list_expected_values, $field_list_html);

    // Assert budget list group.
    $field_list_assert = new DescriptionListAssert();
    $second_field_list_expected_values = [
      'items' => [
        [
          'term' => 'Overall budget',
          'definition' => '€100,00',
        ],
        [
          'term' => 'EU contribution',
          'definition' => '€100,00',
        ],
      ],
    ];
    $field_list_html = $description_lists[1]->getHtml();
    $field_list_assert->assertPattern($second_field_list_expected_values, $field_list_html);

    // Assert details list group.
    $field_list_assert = new DescriptionListAssert();
    $third_field_list_expected_values = [
      'items' => [
        [
          'term' => 'Website',
          'definition' => 'Example website',
        ],
        [
          'term' => 'Funding programme',
          'definition' => 'Anti Fraud Information System (AFIS)',
        ],
        [
          'term' => 'Reference',
          'definition' => 'Project reference',
        ],
      ],
    ];
    $field_list_html = $description_lists[2]->getHtml();
    $field_list_assert->assertPattern($third_field_list_expected_values, $field_list_html);

    // Assert coordinators list group.
    $field_list_assert = new DescriptionListAssert();
    $fourth_field_list_expected_values = [
      'items' => [
        [
          'term' => 'Coordinators',
          'definition' => 'coordinator',
        ],
      ],
    ];
    $field_list_html = $description_lists[3]->getHtml();
    $field_list_assert->assertPattern($fourth_field_list_expected_values, $field_list_html);

    // Assert participants list group.
    $field_list_assert = new DescriptionListAssert();
    $fifth_field_list_expected_values = [
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
    ];
    $field_list_html = $description_lists[4]->getHtml();
    $field_list_assert->assertPattern($fifth_field_list_expected_values, $field_list_html);
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

}
