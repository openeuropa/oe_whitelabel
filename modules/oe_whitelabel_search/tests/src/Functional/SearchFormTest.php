<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_search\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;

/**
 * Search form test.
 */
class SearchFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_bootstrap_theme_helper',
    'oe_whitelabel_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * Test the search form keeps query parameters.
   */
  public function testSearchQueryParams() {
    $options = [
      'query' => [
        'f' => [
          'category:1',
        ],
      ],
    ];
    $search_text = 'Keyword';
    $this->drupalGet('<front>', $options);
    $this->submitSearch($search_text);
    $parsed_url = UrlHelper::parse($this->getSession()->getCurrentUrl());
    $options['query']['text'] = $search_text;
    $this->assertEquals($options['query'], $parsed_url['query']);
  }

  /**
   * Test the search form redirects correctly when action starts with slash.
   */
  public function testSearchActionWithLeadingSlash(): void {
    $this->setSearchBlockFormAction('/search');
    $search_text = 'Keyword';
    $this->drupalGet('<front>');
    $front_url = $this->getSession()->getCurrentUrl();
    $this->assertStringEndsWith('/', $front_url);
    $this->submitSearch($search_text);

    $this->assertSame($front_url . 'search?text=Keyword', $this->getSession()->getCurrentUrl());
  }

  /**
   * Test empty search removes the configured query parameter but keeps others.
   */
  public function testEmptySearchRemovesQueryParam(): void {
    // Make the input not required so the empty submit is allowed.
    $block = $this->container->get('entity_type.manager')->getStorage('block')->load('oe_whitelabel_search_form');
    $this->assertNotNull($block);

    $settings = $block->get('settings');
    $settings['input']['required'] = FALSE;
    $block->set('settings', $settings);
    $block->save();

    $options = [
      'query' => [
        'f' => [
          'category:1',
        ],
        // Simulate an existing search query.
        'text' => 'Old value',
      ],
    ];

    $this->drupalGet('<front>', $options);

    // Submit the search with an empty search string.
    $this->submitSearch('');

    $parsed_url = UrlHelper::parse($this->getSession()->getCurrentUrl());

    // The 'text' param must be removed, but 'f' must remain.
    $this->assertEquals(['f' => ['category:1']], $parsed_url['query']);
  }

  /**
   * Submits the search form on the current page.
   *
   * @param string $search_input
   *   Search string.
   */
  protected function submitSearch(string $search_input): void {
    $page = $this->getSession()->getPage();
    $page->fillField('search_input', $search_input);
    $page->pressButton('Search');
  }

  /**
   * Sets the default search block form action.
   *
   * @param string $action
   *   The form action.
   */
  protected function setSearchBlockFormAction(string $action): void {
    $block = $this->container->get('entity_type.manager')->getStorage('block')->load('oe_whitelabel_search_form');
    $this->assertNotNull($block);

    $settings = $block->get('settings');
    $settings['form']['action'] = $action;
    $block->set('settings', $settings);
    $block->save();
  }

}
