<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\oe_whitelabel\PatternAssertions\CardAssert;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the rendering of the teaser view mode of Project content type.
 */
class ProjectRenderTest extends ContentRenderTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime_range',
    'image',
    'oe_content_extra',
    'oe_content_extra_project',
    'oe_whitelabel_extra_project',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    module_load_include('install', 'oe_whitelabel_extra_project');
    oe_whitelabel_extra_project_install(FALSE);

    $this->installConfig([
      'oe_content_extra_project',
      'oe_whitelabel_extra_project',
    ]);

  }

  /**
   * Test a project being rendered as a teaser.
   */
  public function testProjectTeaser(): void {
    $file = file_save_data(file_get_contents(drupal_get_path('theme', 'oe_whitelabel') . '/tests/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'test image',
      'oe_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Alternative text',
        'caption' => 'Caption',
      ],
    ]);
    $media->save();

    $values = [
      'type' => 'oe_project',
      'title' => 'Project 1',
      'oe_subject' => 'http://data.europa.eu/uxp/1005',
      'oe_teaser' => 'The teaser text',
      'oe_featured_media' => [
        [
          'target_id' => $media->id(),
          'caption' => 'Caption text',
          'alt' => 'Alternative text',
        ],
      ],
      'oe_project_dates' => [
        'value' => '2020-05-10',
        'end_value' => '2025-05-15',
      ],
      'status' => 1,
    ];

    $node = Node::create($values);
    $node->save();

    $build = $this->nodeViewBuilder->view($node, 'teaser');
    $html = $this->renderRoot($build);

    $assert = new CardAssert();

    $expected_values = [
      'title' => 'Project 1',
      'url' => '/node/1',
      'description' => 'The teaser text',
      'badges' => ['EU financing'],
      'image' => [
        'src' => 'example_1.jpeg',
        'alt' => 'Alternative text',
      ],
      'content' => [
        '10 May 2020',
        '15 May 2025',
      ],
    ];
    $assert->assertPattern($expected_values, $html);
    $assert->assertVariant('search', $html);
  }

}
