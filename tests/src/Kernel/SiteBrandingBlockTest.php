<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Site Branding Block rendering.
 */
class SiteBrandingBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_bootstrap_theme_helper',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    $url = '/' . \Drupal::service('extension.list.theme')->getPath('oe_whitelabel') . '/logo.svg';

    \Drupal::configFactory()
      ->getEditable('oe_whitelabel.settings')
      ->set('logo', ['url' => $url])
      ->save();

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('name', 'Site name')
      ->set('slogan', 'Slogan')
      ->save();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBlockRendering(): void {
    \Drupal::configFactory()->getEditable('oe_whitelabel.settings')
      ->set('component_library', 'ec')
      ->save();

    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->load('oe_whitelabel_branding');

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $actual = $crawler->filter('.bcl-header__site-name.site-name');
    $this->assertCount(1, $actual);
    $link = $actual->filter('.text-decoration-none.align-bottom');
    $this->assertCount(1, $link);
    $actual = $crawler->filter('.site-logo.d-none.d-lg-inline-block');
    $this->assertSame(Url::fromRoute('<front>')->toString(), $actual->attr('href'));
    $this->assertCount(1, $actual);
    $logo = $actual->filter('img');
    $this->assertCount(1, $logo);
    $expected = '/themes/custom/oe_whitelabel/logo.svg';
    $this->assertSame($expected, $logo->attr('src'));

    \Drupal::configFactory()->getEditable('oe_whitelabel.settings')
      ->set('component_library', 'eu')
      ->set('header_style', 'light')
      ->save();
    drupal_static_reset('theme_get_setting');

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $actual = $crawler->filter('.bcl-header__site-name.site-name');
    $this->assertCount(1, $actual);
    $link = $actual->filter('.text-decoration-none.align-bottom');
    $this->assertCount(1, $link);
    $actual = $crawler->filter('.site-logo.d-none.d-lg-inline-block');
    $this->assertSame(Url::fromRoute('<front>')->toString(), $actual->attr('href'));
    $this->assertCount(1, $actual);
    $logo = $actual->filter('img');
    $this->assertCount(1, $logo);
    $expected = '/themes/custom/oe_whitelabel/logo.svg';
    $this->assertSame($expected, $logo->attr('src'));

    \Drupal::configFactory()->getEditable('oe_whitelabel.settings')
      ->set('component_library', 'neutral')
      ->save();
    drupal_static_reset('theme_get_setting');

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $actual = $crawler->filter('.bcl-header__site-name.site-name.h5.d-inline-block.d-lg-none');
    $this->assertCount(1, $actual);
    $link = $actual->filter('.text-decoration-none.align-bottom');
    $this->assertSame(Url::fromRoute('<front>')->toString(), $link->attr('href'));
    $this->assertCount(1, $link);
  }

}
