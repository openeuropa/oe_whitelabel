<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\search_api_autocomplete\Entity\Search;
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
    'search_api_test_example_content',
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
    $this->installConfig('search_api_test_example_content');
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
   * Tests the rendering of the search block in the header_top region.
   */
  public function testHeaderTopSearchBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->load('oe_whitelabel_search_form');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Search wrapper div.
    $wrapper = $crawler->filter('div.search-dropdown.dropdown');
    $this->assertCount(1, $wrapper);
    // Toggle button.
    $toggle = $wrapper->filter('button.dropdown-toggle');
    $this->assertCount(1, $toggle);
    $toggle_classes = $toggle->attr('class') ?? '';
    foreach ([
      'btn',
      'btn-ghost',
      'dropdown-toggle',
    ] as $expected_class) {
      $this->assertStringContainsString($expected_class, $toggle_classes);
    }

    // Icon inside toggle.
    $icon = $toggle->filter('svg');
    $this->assertCount(1, $icon);
    $icon_classes = $icon->attr('class') ?? '';
    $this->assertStringContainsString('icon--fluid', $icon_classes);
    $this->assertStringContainsString('bi', $icon_classes);

    // Dropdown menu container.
    $dropdown = $wrapper->filter('div.dropdown-menu');
    $this->assertCount(1, $dropdown);
    $this->assertSame('dropdown-menu', $dropdown->attr('class'));

    // The form.
    $form = $dropdown->filter('form');
    $this->assertCount(1, $form);
    $this->assertSame('oe-whitelabel-search-form', $form->attr('id'));
    $this->assertSame('d-flex', $form->attr('class'));

    // Text input field.
    $input = $form->filter('input[name="search_input"]');
    $this->assertCount(1, $input);
    $this->assertSame('required form-control rounded-0 rounded-start', $input->attr('class'));
    $this->assertSame('Search', $input->attr('placeholder'));

    // Submit button.
    $button = $form->filter('button#edit-submit');
    $this->assertCount(1, $button);
    $this->assertSame('Search', trim($button->text()));
    $this->assertSame('Search', $button->attr('value'));
    $button_classes = $button->attr('class');
    $this->assertNotNull($button_classes);
    $this->assertStringContainsString('button', $button_classes);
    $this->assertStringContainsString('js-form-submit', $button_classes);
    $this->assertStringContainsString('form-submit', $button_classes);
    $this->assertStringContainsString('border-start-0', $button_classes);
    $this->assertStringContainsString('rounded-0', $button_classes);
    $this->assertStringContainsString('rounded-end', $button_classes);
    $this->assertStringContainsString('px-3', $button_classes);
    $this->assertStringContainsString('btn', $button_classes);
    $this->assertStringContainsString('btn-primary', $button_classes);

    // Icon inside submit button.
    $submit_icon = $button->filter('svg');
    $this->assertCount(1, $submit_icon);
    $submit_icon_classes = $submit_icon->attr('class') ?? '';
    $this->assertStringContainsString('icon--fluid', $submit_icon_classes);
    $this->assertStringContainsString('bi', $submit_icon_classes);

    // Hidden form_id input.
    $form_id_input = $form->filter('input[name="form_id"]');
    $this->assertCount(1, $form_id_input);
    $this->assertSame('oe_whitelabel_search_form', $form_id_input->attr('value'));
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
          'label' => 'Search',
          'placeholder' => 'Search',
          'required' => TRUE,
        ],
        'button' => [
          'label' => 'Search',
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
    $button = $form->filter('button');
    $this->assertCount(1, $button);
    $this->assertStringContainsString('bcl-search-form__submit', $button->attr('class'));
    $this->assertStringContainsString('btn', $button->attr('class'));
    $this->assertStringContainsString('btn-primary', $button->attr('class'));
    $this->assertStringContainsString('btn-md', $button->attr('class'));
    $this->assertStringContainsString('gap-2-5', $button->attr('class'));
    $icon = $button->filter('.bi.icon--fluid.bcl-search-form__btn_icon');
    $this->assertCount(1, $icon);
    $label = $button->filter('span.d-none.d-lg-inline-block');
    $this->assertCount(1, $label);
    $this->assertEquals('Search', $label->text());
  }

  /**
   * Tests the header search block input is not required when configured so.
   */
  public function testHeaderSearchBlockInputNotRequired(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');

    $entity = $block_entity_storage->create([
      'id' => 'whitelabel_search_block_not_required',
      'theme' => 'oe_whitelabel',
      'plugin' => 'whitelabel_search_block',
      'settings' => [
        'id' => 'whitelabel_search_block_not_required',
        'label' => 'Header Search block (not required)',
        'provider' => 'oe_whitelabel_search',
        'form' => [
          'action' => 'search',
          'region' => 'header',
        ],
        'input' => [
          'name' => 'search_api_fulltext',
          'label' => 'Search',
          'placeholder' => 'Search',
          'required' => FALSE,
        ],
        'button' => [
          'label' => 'Search',
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

    $input = $crawler->filter('input[name="search_input"]');
    $this->assertCount(1, $input);

    // When not required, no "required" HTML attribute should be present.
    $this->assertNull($input->attr('required'));

    // And the "required" CSS class should not be present.
    $classes = $input->attr('class') ?? '';
    $this->assertStringNotContainsString('required', $classes);
  }

}
