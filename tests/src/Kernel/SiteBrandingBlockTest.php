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
  protected static $modules = [
    'block',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    $this->config('oe_whitelabel.settings')
      ->set('logo', ['url' => '/path/to/theme/resources/logo.svg'])
      ->save();

    $this->config('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    $this->config('system.site')
      ->set('name', 'Site name')
      ->save();
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
    $link = $actual->filter('.text-white.text-decoration-none.align-bottom');
    $this->assertCount(1, $link);
    $actual = $crawler->filter('.site-logo');
    $this->assertCount(1, $actual);
    $logo = $actual->filter('img');
    $this->assertCount(1, $logo);
    $this->assertSame('/path/to/theme/resources/logo.svg', $logo->attr('src'));
  }

}
