<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the rendering of the entity reference fields for paragraphs.
 */
class EntityReferenceRevisionTest extends ParagraphsTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->createContentType([
      'type' => 'paragraphs_test',
      'name' => 'Paragraphs Test',
    ]);
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'paragraphs_test',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ])->save();

    $this->container->get('theme_installer')->install(['oe_whitelabel']);
    $this->config('system.theme')->set('default', 'oe_whitelabel')->save();

    $this->setCurrentUser($this->createUser([], '', TRUE));
  }

  /**
   * Test the rendering of an entity reference revision field.
   */
  public function testEntityReferenceRevisionField(): void {
    $paragraph1 = Paragraph::create([
      'type' => 'oe_links_block',
      'field_oe_text' => 'More information',
      'oe_w_links_block_orientation' => 'vertical',
      'oe_w_links_block_background' => 'gray',
      'field_oe_links' => [
        [
          'title' => 'European Commission',
          'uri' => 'https://example.com',
        ],
      ],
    ]);
    $paragraph1->save();

    $paragraph2 = Paragraph::create([
      'type' => 'oe_rich_text',
      'field_oe_title' => 'Rich text example',
    ]);
    $paragraph2->save();

    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type' => 'paragraphs_test',
      'title' => 'Test node',
      'test_paragraphs' => [$paragraph1, $paragraph2],
      'uid' => 0,
      'status' => 1,
    ]);
    $node->save();

    $build = $node->get('test_paragraphs')->view();
    $html = $this->render($build);
    $crawler = new Crawler($html);

    $wrappers = $crawler->filter('div.my-4');
    $this->assertCount(2, $wrappers);
    $rich_text = $wrappers->eq(1);
    // Assert the paragraphs where rendered.
    $this->assertStringContainsString('European Commission', $wrappers->eq(0)->text());
    $this->assertStringContainsString('Rich text example', $rich_text->text());
  }

}
