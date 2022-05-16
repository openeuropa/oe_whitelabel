<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api_autocomplete\Entity\Search;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Search Block rendering.
 */
class SearchBlockTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'field',
    'oe_bootstrap_theme_helper',
    'oe_whitelabel_search',
    'search_api',
    'search_api_autocomplete',
    'search_api_autocomplete_test',
    'search_api_db',
    'search_api_test',
    'system',
    'text',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    $this->config('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');
    $this->installConfig([
      'search_api_db',
      'search_api_autocomplete_test',
    ]);

    Search::create([
      'id' => 'search_api_autocomplete_test_view',
      'label' => 'Search API Autocomplete Test view',
      'index_id' => 'autocomplete_search_index',
      'suggester_settings' => [
        'live_results' => [],
      ],
      'search_settings' => [
        'views:search_api_autocomplete_test_view' => [
          'displays' => [
            'default' => TRUE,
            'selected' => ['page_2'],
          ],
        ],
      ],
    ])->save();

    // Add user with permissions for the autocomplete feature.
    $this->setUpCurrentUser(['uid' => 1]);
  }

  /**
   * Tests the rendering of the whitelabel search block navigation_right region.
   */
  public function testNavigationRightSearchBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->load('oe_whitelabel_search_form');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Assert the form rendering.
    $form = $crawler->filter('form');
    $this->assertCount(1, $form);
    $this->assertSame('oe-whitelabel-search-form', $form->attr('id'));
    $this->assertStringContainsString('d-flex', $form->attr('class'));
    // Assert search text box.
    $input = $crawler->filter('input[name="search_input"]');
    $this->assertCount(1, $input);
    $classes = 'required form-control border-start-0 rounded-0 rounded-start';
    $this->assertSame($classes, $input->attr('class'));
    $this->assertSame('Search', $input->attr('placeholder'));
    // Assert the button and icon rendering.
    $button = $crawler->filter('button#submit');
    $this->assertCount(1, $button);
    $this->assertStringContainsString('rounded-end', $button->attr('class'));
    $this->assertStringContainsString('rounded-0', $button->attr('class'));
    $this->assertStringContainsString('border-start-0', $button->attr('class'));
    $this->assertStringContainsString('btn', $button->attr('class'));
    $this->assertStringContainsString('btn-md', $button->attr('class'));
    $this->assertStringContainsString('btn-light', $button->attr('class'));
    $icon = $button->filter('.bi.icon--fluid');
    $this->assertCount(1, $icon);
  }

  /**
   * Tests the rendering of the whitelabel search block header region.
   */
  public function testHeaderSearchBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->create([
      'id' => 'whitelabel_search_block',
      'theme' => 'oe_whitelabel',
      'plugin' => 'whitelabel_search_block',
      'settings' => [
        'id' => 'whitelabel_search_block',
        'label' => 'Header Search block',
        'provider' => 'oe_whitelabel_search',
        'form' => [
          'action' => 'search',
          'region' => 'header',
        ],
        'input' => [
          'name' => 'search_api_fulltext',
        ],
        'view_options' => [
          'enable_autocomplete' => TRUE,
          'id' => 'search_api_autocomplete_test_view',
          'display' => 'default',
        ],
      ],
    ]);
    $entity->save();

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Assert header form wrappers.
    $wrapper = $crawler->filter(
      'div.bg-lighter > div.container > div.row > div.col-12.col-lg-6.offset-lg-3'
    );
    $this->assertCount(1, $wrapper);
    // Assert the form rendering.
    $form = $wrapper->filter('form');
    $this->assertCount(1, $form);
    $this->assertSame('oe-whitelabel-search-form', $form->attr('id'));
    $this->assertSame('bcl-search-form submittable', $form->attr('class'));
    // Assert the field wrapper rendering.
    $wrapper = $form->filter('.bcl-search-form__group');
    $this->assertCount(1, $wrapper);
    // Assert search text box.
    $input = $crawler->filter('input[name="search_input"]');
    $this->assertCount(1, $input);
    $classes = 'form-autocomplete required form-control bcl-search-form__input';
    $this->assertSame($classes, $input->attr('class'));
    $this->assertSame('Search', $input->attr('placeholder'));
    // Assert the button and icon rendering.
    $button = $crawler->filter('button#submit');
    $this->assertCount(1, $button);
    $this->assertStringContainsString('bcl-search-form__submit', $button->attr('class'));
    $this->assertStringContainsString('btn', $button->attr('class'));
    $this->assertStringContainsString('btn-primary', $button->attr('class'));
    $this->assertStringContainsString('btn-md', $button->attr('class'));
    $icon = $button->filter('.bi.icon--fluid');
    $this->assertCount(1, $icon);
    $label = $button->filter('span.d-none.d-lg-inline-block');
    $this->assertCount(1, $label);
    $this->assertEquals('Search', $label->text());
  }

}
