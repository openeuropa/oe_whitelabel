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
    $page = $this->getSession()->getPage();
    $page->fillField('search_input', $search_text);
    $page->pressButton('Search');
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
    $page = $this->getSession()->getPage();
    $page->fillField('search_input', $search_text);
    $page->pressButton('Search');

    $current_url = $this->getSession()->getCurrentUrl();
    $current_path = parse_url($current_url, PHP_URL_PATH);
    $parsed_url = UrlHelper::parse($current_url);

    $this->assertStringEndsWith('/search', $current_path);
    $this->assertStringNotContainsString('//', $current_path);
    $this->assertEquals(['text' => $search_text], $parsed_url['query']);
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
    $page = $this->getSession()->getPage();

    // Clear the prefilled default value coming from the query string.
    $page->fillField('search_input', '');

    // Submit without filling the field -> empty string.
    $page->pressButton('Search');

    $parsed_url = UrlHelper::parse($this->getSession()->getCurrentUrl());

    // The 'text' param must be removed, but 'f' must remain.
    $this->assertEquals(['f' => ['category:1']], $parsed_url['query']);
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
