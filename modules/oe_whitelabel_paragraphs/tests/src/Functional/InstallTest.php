<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests installation of oe_whitelabel_paragraphs.
 *
 * @see oe_whitelabel_paragraphs_install()
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_whitelabel_legacy_paragraphs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test installation with legacy fields and data present.
   */
  public function testInstallWithLegacyParagraphs(): void {
    $paragraphs_data = [];
    $paragraphs_data['oe_description_list'] = [
      'type' => 'oe_description_list',
      'field_oe_title' => 'Description list paragraph',
      // This field will be renamed.
      'oe_bt_orientation' => 'horizontal',
      'field_oe_description_list_items' => [
        // One item is enough for this test.
        [
          'term' => 'Aliquam ultricies',
          'description' => 'Donec et leo ac velit posuere tempor mattis ac mi. Vivamus nec dictum lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
        ],
      ],
    ];
    $paragraphs_data['oe_facts_figures'] = [
      'type' => 'oe_facts_figures',
      'field_oe_title' => 'Fact and figures block',
      'field_oe_link' => [
        'uri' => 'https://www.readmore.com',
        'title' => 'Read more',
      ],
      // This field will be renamed.
      'oe_bt_n_columns' => 3,
      'field_oe_paragraphs' => [
        'type' => 'oe_fact',
        'field_oe_icon' => 'box-arrow-up',
        'field_oe_title' => '1529 JIRA Ticket',
        'field_oe_subtitle' => 'Jira Tickets',
        'field_oe_plain_text_long' => 'Nunc condimentum sapien ut nibh finibus suscipit vitae at justo. Morbi quis odio faucibus, commodo tortor id, elementum libero.',
      ],
    ];
    $paragraphs_data['oe_links_block'] = [
      'type' => 'oe_links_block',
      'field_oe_text' => 'More information',
      // These fields will be renamed.
      'oe_bt_links_block_orientation' => 'vertical',
      'oe_bt_links_block_background' => 'gray',
      'field_oe_links' => [
        // One link is enough for this test.
        [
          'title' => 'European Commission',
          'uri' => 'https://example.com',
        ],
      ],
    ];
    $paragraphs_data['oe_social_media_follow'] = [
      'type' => 'oe_social_media_follow',
      'field_oe_title' => 'Social media title',
      'field_oe_social_media_variant' => 'horizontal',
      // This field will be renamed.
      'oe_bt_links_block_background' => 'gray',
      // One link is enough for this test.
      'field_oe_social_media_links' => [
        [
          'title' => 'Email',
          'uri' => 'mailto:example@com',
          'link_type' => 'email',
        ],
      ],
      'field_oe_social_media_see_more' => [
        'title' => 'Other social networks',
        'uri' => 'https://europa.eu/european-union/contact/social-networks_en',
      ],
    ];

    $revision_ids = [];
    foreach ($paragraphs_data as $name => $paragraph_data) {
      $paragraph = Paragraph::create($paragraph_data);
      $paragraph->save();
      $revision_ids[$name] = $paragraph->getRevisionId();
      if ($name !== 'oe_links_block') {
        // Don't create a revision for most of the paragraphs.
        continue;
      }
      // Make this paragraph a revision.
      $paragraph->setNewRevision();
      $paragraph->oe_bt_links_block_orientation = 'horizontal';
      $paragraph->save();
      $revision_ids[$name . ':modified'] = $paragraph->getRevisionId();
    }

    $legacy_field_config_ids = [
      'paragraph.oe_description_list.oe_bt_orientation',
      'paragraph.oe_facts_figures.oe_bt_n_columns',
      'paragraph.oe_links_block.oe_bt_links_block_background',
      'paragraph.oe_links_block.oe_bt_links_block_orientation',
      'paragraph.oe_social_media_follow.oe_bt_links_block_background',
    ];
    $legacy_field_storage_ids = [
      'paragraph.oe_bt_links_block_background',
      'paragraph.oe_bt_links_block_orientation',
      'paragraph.oe_bt_n_columns',
      'paragraph.oe_bt_orientation',
    ];
    $this->assertEqualsCanonicalizing(
      $legacy_field_config_ids,
      array_keys(FieldConfig::loadMultiple($legacy_field_config_ids)),
    );
    $this->assertEqualsCanonicalizing(
      $legacy_field_storage_ids,
      array_keys(FieldStorageConfig::loadMultiple($legacy_field_storage_ids)),
    );

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $installer */
    $installer = \Drupal::service('module_installer');
    $installer->install(['oe_whitelabel_paragraphs']);

    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('oe_whitelabel_paragraphs'),
      "Module 'oe_whitelabel_paragraphs was successfully installed.");

    $this->assertEmpty(FieldConfig::loadMultiple($legacy_field_config_ids));
    $this->assertEmpty(FieldStorageConfig::loadMultiple($legacy_field_storage_ids));

    $expected_created = [
      'oe_description_list' => [
        'oe_w_orientation' => 'horizontal',
      ],
      'oe_facts_figures' => [
        'oe_w_n_columns' => '3',
      ],
      'oe_links_block' => [
        'oe_w_links_block_orientation' => 'vertical',
        'oe_w_links_block_background' => 'gray',
      ],
      'oe_links_block:modified' => [
        'oe_w_links_block_orientation' => 'horizontal',
        'oe_w_links_block_background' => 'gray',
      ],
      'oe_social_media_follow' => [
        'oe_w_links_block_background' => 'gray',
      ],
    ];

    $expected_deleted = [
      'oe_description_list' => [
        'oe_bt_orientation' => TRUE,
      ],
      'oe_facts_figures' => [
        'oe_bt_n_columns' => TRUE,
      ],
      'oe_links_block' => [
        'oe_bt_links_block_orientation' => TRUE,
        'oe_bt_links_block_background' => TRUE,
      ],
      'oe_links_block:modified' => [
        'oe_bt_links_block_orientation' => TRUE,
        'oe_bt_links_block_background' => TRUE,
      ],
      'oe_social_media_follow' => [
        'oe_bt_links_block_background' => TRUE,
      ],
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Produce reports instead of many individual assertions. This is less
    // simple in code, but produces more useful output on test failure.
    $actual_updated = [];
    $actual_deleted = [];
    foreach ($revision_ids as $name => $revision_id) {
      $updated_revision = $storage->loadRevision($revision_id);
      $this->assertNotNull($updated_revision);
      foreach ($expected_created[$name] as $field_name => $value) {
        if (!$updated_revision->hasField($field_name)) {
          // The expected field was not created.
          // Omit this entry in $actual_updated, to cause a fail below.
          continue;
        }
        // The expected field was created, but the value might be wrong.
        $actual_updated[$name][$field_name] = $updated_revision->get($field_name)->value;
      }
      foreach ($expected_deleted[$name] as $field_name => $deleted) {
        $actual_deleted[$name][$field_name] = !$updated_revision->hasField($field_name);
      }
    }

    // Compare the reports to the expected values.
    $this->assertSame($expected_created, $actual_updated);
    $this->assertSame($expected_deleted, $actual_deleted);
  }

}
