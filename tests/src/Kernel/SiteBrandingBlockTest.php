<?php

declare(strict_types=1);

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

    $this->assertBrandingHeading($crawler);

    \Drupal::configFactory()->getEditable('oe_whitelabel.settings')
      ->set('component_library', 'eu')
      ->set('header_style', 'light')
      ->save();
    drupal_static_reset('theme_get_setting');

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $this->assertBrandingHeading($crawler);

    \Drupal::configFactory()->getEditable('oe_whitelabel.settings')
      ->set('component_library', 'neutral')
      ->save();
    drupal_static_reset('theme_get_setting');

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler((string) $render);

    $this->assertBrandingHeading($crawler);
  }

  /**
   * Asserts the branding block renders the site name heading.
   */
  protected function assertBrandingHeading(Crawler $crawler): void {
    $container = $crawler->filter('div.container');
    $this->assertCount(1, $container);

    $heading = $container->filter('#site-name-heading');
    $this->assertCount(1, $heading);

    $tag = $heading->nodeName();
    $this->assertContains($tag, ['h1', 'p']);
    $this->assertSame('Site name', trim($heading->text()));

    $classes = array_filter(explode(' ', (string) $heading->attr('class')));
    foreach (['h5', 'py-3-5', 'border-top-subtle', 'mb-0'] as $expected_class) {
      $this->assertContains($expected_class, $classes);
    }
  }

}
