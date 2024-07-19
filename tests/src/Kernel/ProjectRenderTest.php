<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CardPatternAssert;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the rendering of the teaser view mode of Project content type.
 */
class ProjectRenderTest extends ContentRenderTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'image',
    'oe_content_featured_media_field',
    'oe_content_project',
    'oe_content_extra',
    'oe_content_extra_project',
    'oe_whitelabel_extra_project',
    'system',
    'twig_field_value',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'oe_content_featured_media_field',
      'oe_content_project',
      'oe_content_extra',
      'oe_content_extra_project',
      'oe_whitelabel_extra_project',
    ]);

    $this->container->get('module_handler')->loadInclude('oe_whitelabel_extra_project', 'install');
    oe_whitelabel_extra_project_install(FALSE);
  }

  /**
   * Test a project being rendered as a teaser.
   */
  public function testProjectTeaser(): void {
    $file = \Drupal::service('file.repository')->writeData(file_get_contents(\Drupal::service('extension.list.theme')->getPath('oe_whitelabel') . '/tests/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
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

    $assert = new CardPatternAssert();

    $expected_values = [
      'title' => 'Project 1',
      'url' => '/node/1',
      'description' => 'The teaser text',
      'badges' => [
        // The project status is only calculated when JavaScript is executed.
        '&hellip;',
        'EU financing',
      ],
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
