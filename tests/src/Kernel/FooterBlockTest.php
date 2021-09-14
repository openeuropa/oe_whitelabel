<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the EU and the EC corporate Footer blocks rendering.
 */
class FooterBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'components',
    'oe_whitelabel_helper',
    'oe_whitelabel_footer',
    'ui_patterns',
    'ui_patterns_library',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
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
  public function testEcFooterBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'ecfooterblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_corporate_blocks_ec_footer',
      'settings' => [
        'id' => 'oe_corporate_blocks_ec_footer',
        'label' => 'EC Footer block',
        'provider' => 'oe_corporate_blocks',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    var_dump($render);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('footer');
    $this->assertCount(1, $actual);
    $sections = $actual->filter('.footer-section');
    $this->assertCount(3, $sections);
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testEuFooterBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'eufooterblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_corporate_blocks_eu_footer',
      'settings' => [
        'id' => 'oe_corporate_blocks_eu_footer',
        'label' => 'EU Footer block',
        'provider' => 'oe_corporate_blocks',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('footer');
    $this->assertCount(1, $actual);
    $sections = $actual->filter('.footer-section');
    $this->assertCount(6, $sections);
  }

}
