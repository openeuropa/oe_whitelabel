<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the FloatEndSort exposed form sort widget rendering.
 */
class FloatEndSortWidgetTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bef_test',
    'better_exposed_filters',
    'field',
    'filter',
    'node',
    'oe_whitelabel_helper',
    'options',
    'system',
    'taxonomy',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests the rendering of the FloatEndSortWidget.
   */
  public function testFloatEndSortWidget() {
    $view = Views::getView('bef_test');
    $view->storage->getDisplay('default');

    $this->setBetterExposedOptions($view, [
      'sort' => [
        'plugin_id' => 'oe_whitelabel_float_end_sort',
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);
    $crawler = new Crawler($this->content->__toString());

    $widget = $crawler->filter('form.bef-exposed-form');
    $this->assertCount(1, $widget);
    $this->assertStringContainsString('float-lg-end d-none d-md-flex align-items-baseline', $widget->attr('class'));

    $view->destroy();
  }

}
