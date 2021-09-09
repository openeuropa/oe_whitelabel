<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Corporate Logo Block rendering.
 */
class CorporateLogoBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'components',
    'ui_patterns',
    'ui_patterns_library',
    'user',
    'system',
    'oe_whitelabel_helper',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();
    $this->container->set('theme.registry', NULL);

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
      'id' => 'corporatelogoblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'whitelabel_logo_block',
      'settings' => [
        'id' => 'whitelabel_logo_block',
        'label' => 'Corporate Logo Block',
        'provider' => 'oe_whitelabel_helper',
        'label_display' => '0',
        'logo_source' => 'ec',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('#block-corporatelogoblock');
    $this->assertCount(1, $actual);
    $logo = $actual->filter('img');
    $this->assertCount(1, $logo);
    $logo_path = drupal_get_path('module', 'oe_whitelabel_helper') . '/images/logos/ec';
    $expected = '/' . $logo_path . '/europa-flag.gif';
    $this->assertSame($expected, $logo->attr('src'));
  }

}
