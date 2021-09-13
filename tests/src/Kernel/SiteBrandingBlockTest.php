<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Site Branding Block rendering.
 */
class SiteBrandingBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    $this->container->set('theme.registry', NULL);
    $url = '/' . drupal_get_path('theme', 'oe_whitelabel') . '/logo.svg';

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

    $this->container->get('cache.render')->deleteAll();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'test_block',
      'theme' => 'oe_whitelabel',
      'plugin' => 'system_branding_block',
      'settings' => [
        'id' => 'system_branding_block',
        'label' => 'Site branding',
        'provider' => 'system',
        'label_display' => '0',
        'use_site_logo' => TRUE,
        'use_site_name' => TRUE,
        'use_site_slogan' => FALSE,
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('.site-name');
    $this->assertCount(1, $actual);
    $link = $actual->filter('.h1.text-white.text-decoration-none.align-bottom');
    $this->assertCount(1, $link);
    $actual = $crawler->filter('.site-logo');
    $this->assertCount(1, $actual);
    $logo = $actual->filter('img');
    $this->assertCount(1, $logo);
    $expected = '/themes/custom/oe_whitelabel/logo.svg';
    $this->assertSame($expected, $logo->attr('src'));
  }

}
