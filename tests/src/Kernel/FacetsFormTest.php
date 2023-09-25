<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Facets Form rendering.
 */
class FacetsFormTest extends AbstractKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'facets',
    'facets_form',
    'field',
    'rest',
    'search_api',
    'search_api_db',
    'search_api_test',
    'search_api_test_db',
    'search_api_test_example_content',
    'search_api_test_views',
    'serialization',
    'text',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    $this->installConfig([
      'search_api_test_example_content',
      'search_api_test_db',
    ]);
    $this->installConfig('search_api_test_views');
  }

  /**
   * Tests the rendering of the Facets Form block.
   */
  public function testBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $block = $block_entity_storage->create([
      'id' => 'whitelabel_facets_form_block',
      'theme' => 'oe_whitelabel',
      'plugin' => 'facets_form:search_api:views_page__search_api_test_view__page_1',
      'settings' => [
        'id' => 'facets_form:search_api:views_page__search_api_test_view__page_1',
        'label' => 'Facets form',
        'provider' => 'facets_form',
        'button' => [
          'label' => [
            'submit' => 'Search',
            'reset' => 'Clear filters',
          ],
        ],
        'facets' => [],
      ],
    ]);
    $block->save();

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($block, 'block');
    $html = (string) $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($html);

    $offcanvas = $crawler->filter('div#bcl-offcanvas');
    $header = $offcanvas->filter('div.offcanvas-header');
    $this->assertCount(1, $header);
    $title = $header->filter('.offcanvas-title');
    $this->assertSame('Facets form', $title->text());
    $button = $header->filter('button');
    $this->assertSame('offcanvas', $button->attr('data-bs-dismiss'));
    $this->assertSame('button', $button->attr('type'));

    $body = $offcanvas->filter('div.offcanvas-body.bcl-offcanvas');
    $this->assertCount(1, $body);
    $form = $body->filter('form.facets-form');
    $this->assertCount(1, $form);

    $button = $crawler->filter('button.btn-light.btn-lg');
    $this->assertSame('button', $button->attr('type'));
    $this->assertSame('#bcl-offcanvas', $button->attr('data-bs-target'));
    $this->assertSame('offcanvas', $button->attr('data-bs-toggle'));
    $this->assertStringContainsString('Facets form', $button->text());

    $icon = $button->filter('svg');
    $this->assertStringContainsString('/assets/icons/bcl-default-icons.svg#filter', $icon->html());
  }

}
