<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests pager.
 */
class PagerKernelTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_pager_full', 'test_pager_some'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests pager-related setter methods on ViewExecutable.
   *
   * @see \Drupal\views\ViewExecutable::setItemsPerPage
   * @see \Drupal\views\ViewExecutable::setOffset
   * @see \Drupal\views\ViewExecutable::setCurrentPage
   */
  public function testFullPagerMethods() {
    $view = Views::getView('test_pager_full');

    // Mark the view as cacheable in order have the cache checking working
    // below.
    $view->setDisplay();
    $view->displayHandlers->get('default')->setOption('pager', [
      'type' => 'mini',
      'options' => ['items_per_page' => 5],
    ]);
    $view->storage->save();

    $output = $view->preview();

    \Drupal::service('renderer')->renderPlain($output);
    $this->assertSame(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age']);

    foreach (['setItemsPerPage', 'setOffset', 'setCurrentPage'] as $method) {
      $view = Views::getView('test_pager_full');
      $view->setDisplay('default');
      $view->{$method}(1);
      $output = $view->preview();

      \Drupal::service('renderer')->renderPlain($output);
      $this->assertSame(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age'], 'Max age kept.');
    }

  }

  /**
   * Tests pager-related setter methods on ViewExecutable.
   *
   * @see \Drupal\views\ViewExecutable::setItemsPerPage
   * @see \Drupal\views\ViewExecutable::setOffset
   * @see \Drupal\views\ViewExecutable::setCurrentPage
   */
  public function testMiniPagerMethods() {
    $view = Views::getView('test_pager_some');

    // Mark the view as cacheable in order have the cache checking working
    // below.
    $view->setDisplay();
    $view->displayHandlers->get('default')->setOption('pager', [
      'type' => 'mini',
      'options' => ['items_per_page' => 10],
    ]);
    $view->storage->save();
    $output = $view->preview();

    \Drupal::service('renderer')->renderPlain($output);
    $this->assertSame(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age']);

    foreach (['setItemsPerPage', 'setOffset', 'setCurrentPage'] as $method) {
      $view = Views::getView('test_pager_some');
      $view->setDisplay('default');
      $view->{$method}(1);
      $output = $view->preview();

      \Drupal::service('renderer')->renderPlain($output);
      $this->assertSame(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age'], 'Max age kept.');
    }

  }

}
