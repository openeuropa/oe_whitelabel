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
   * Tests the rendering of the whitelabel search block.
   */
  public function testBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->create([
      'id' => 'whitelabel_search_block',
      'theme' => 'oe_whitelabel',
      'plugin' => 'whitelabel_search_block',
      'settings' => [
        'id' => 'search_block',
        'label' => 'Search block',
        'provider' => 'oe_whitelabel_search',
        'form' => [
          'action' => '/search',
        ],
        'input' => [
          'name' => 'text',
          'label' => 'Search',
          'placeholder' => 'Search',
          'classes' => 'input-test-class',
        ],
        'button' => [
          'classes' => 'button-test-class',
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

    // Select the search form in the block.
    // The block template removes the block wrapper, so the form is the root
    // element.
    $form = $crawler->filter('body > form#oe-whitelabel-search-form');
    $this->assertCount(1, $form);
    $this->assertSame('d-flex mt-3 mt-lg-0', $form->attr('class'));
    // Assert the field wrapper rendering.
    $wrapper = $form->filter('.bcl-search-form__group');
    $this->assertCount(1, $wrapper);
    // Assert search text box.
    $input = $crawler->filter('.input-test-class');
    $this->assertCount(1, $input);
    $classes = 'input-test-class rounded-0 rounded-start form-autocomplete required form-control';
    $this->assertSame($classes, $input->attr('class'));
    $this->assertSame('Search', $input->attr('placeholder'));
    // Assert the hidden label.
    $label = $wrapper->filter('label');
    $this->assertSame('Search', $label->text());
    $classes = 'visually-hidden js-form-required form-required form-label';
    $this->assertSame($classes, $label->attr('class'));
    // Assert the button and icon rendering.
    $button = $crawler->filter('.button-test-class');
    $this->assertCount(1, $button);
    $classes = 'border-start-0 rounded-0 rounded-end d-flex btn btn-light btn-md py-2 button-test-class btn btn-light';
    $this->assertSame($classes, $button->attr('class'));
    $icon = $button->filter('.bi.icon--fluid');
    $this->assertCount(1, $icon);
  }

}
