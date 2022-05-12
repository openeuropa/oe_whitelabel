<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel\Plugin\better_exposed_filters\sort;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests theming of the "Float End Sort" widget for "better exposed filters".
 *
 * @coversDefaultClass \Drupal\oe_whitelabel_helper\Plugin\better_exposed_filters\sort\FloatEndSortWidget
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
      // @todo Add combined sort.
      'sort' => [
        'plugin_id' => 'oe_whitelabel_float_end_sort',
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);
    $crawler = new Crawler($this->content->__toString());

    $widget = $crawler->filter('form.bef-exposed-form');
    $this->assertCount(1, $widget);
    $this->assertStringContainsString('col-md-6 col-lg-4 mt-3 mt-md-0', $widget->attr('class'));

    $view->destroy();
  }

}
