<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel\Plugin\better_exposed_filters\sort;

use Drupal\Core\Site\Settings;
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
  protected static $modules = [
    'better_exposed_filters',
    'field',
    'filter',
    'node',
    'oe_whitelabel_helper',
    'oewt_views_test',
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
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Replicate 'file_scan_ignore_directories' from settings.php.
    $settings = Settings::getAll();
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
      'vendor',
      'build',
    ];
    new Settings($settings);

    // @todo Use bef_test once it's fixed for d10.
    // @see https://www.drupal.org/project/better_exposed_filters/issues/3365130
    $this->installConfig(['oewt_views_test']);
  }

  /**
   * Tests the rendering of the FloatEndSortWidget.
   */
  public function testFloatEndSortWidget() {
    $view = Views::getView('oewt');

    // Render the exposed form.
    $this->renderExposedForm($view);

    $crawler = new Crawler($this->content->__toString());
    $widget = $crawler->filter('form.bef-exposed-form');

    $this->assertCount(1, $widget);
    $this->assertStringContainsString('col-lg-4 col-md-6 mt-3 mt-md-0', $widget->attr('class'));

    $view->destroy();
  }

}
