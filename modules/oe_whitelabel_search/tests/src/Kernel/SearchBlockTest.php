<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_autocomplete\Entity\Search;
use Drupal\views\Entity\View;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Site Branding Block rendering.
 */
class SearchBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'system',
    'text',
    'user',
    'search_api',
    'search_api_test',
    'search_api_test_example_content',
    'search_api_autocomplete',
    'search_api_autocomplete_test',
    'search_api_db',
    'oe_whitelabel_search',
    'views',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    $this->container->set('theme.registry', NULL);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');
    $this->installConfig([
      'search_api_db',
      'search_api_test_example_content',
//      'search_api_test_db',
//      'search_api_db_test_autocomplete',
      'search_api_autocomplete_test',
    ]);

    $this->container->get('cache.render')->deleteAll();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBlockRendering(): void {
    $this->installConfig('search_api_autocomplete_test');
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

    $view = View::load('search_api_autocomplete_test_view');
    $executable = $view->getExecutable();
    $this->assertTrue($executable->setDisplay('default'));
    $executable->initHandlers();
    $exposed_form = $executable->display_handler->getPlugin('exposed_form');
    $form = $exposed_form->renderExposedForm();
    $keys_element = $form['keys'] ?? $form['keys_wrapper']['keys'];
    $this->assertEquals('search_api_autocomplete', $keys_element['#type']);

    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'whitelabel_search_block',
      'theme' => 'oe_whitelabel',
      'plugin' => 'whitelabel_search_block',
      'settings' => [
        'id' => 'search_block',
        'label' => 'Search block',
        'provider' => 'oe_whitelabel_search',
        'form_action' => 'en/search',
        'input_name' => 'search',
        'input_placeholder' => 'Search',
        'button_label' => 'Search',
        'button_type' => 'submit',
        'button_icon_position' => 'top',
        'view_id' => 'search_api_autocomplete_test_view',
        'view_display' => 'default',
        'enable_autocomplete' => TRUE,
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());
    $actual = $crawler->filter('.oe-whitelabel-search-form');
    $this->assertCount(1, $actual);
    $link = $actual->filter('.button.btn-primary');
    $this->assertCount(1, $link);
    $expected = "Search";
    $title = $actual->filter('input.form-control');
    $this->assertSame($expected, $title->attr("placeholder"));
    $expected = "en/search";
    $title = $actual->filter('form');
    $this->assertSame($expected, $title->attr("action"));
  }

}
