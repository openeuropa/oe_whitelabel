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

}
