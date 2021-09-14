<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the OE Multilingual Block rendering.
 */
class MultilingualBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'components',
    'content_translation',
    'ctools',
    'language',
    'locale',
    'node',
    'oe_multilingual',
    'path',
    'pathauto',
    'path_alias',
    'system',
    'token',
    'ui_patterns',
    'ui_patterns_library',
    'user',
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
  public function testBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'languageswitchercontent',
      'theme' => 'oe_whitelabel',
      'plugin' => 'language_block:language_content',
      'settings' => [
        'id' => 'language_block:language_content',
        'label' => 'Language switcher (Content)',
        'provider' => 'language',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    print_r($render);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('#block-languageswitchercontent');
    $this->assertCount(1, $actual);
    $link = $crawler->filter('a');
    $this->assertSame('English', $link->text());
    $this->assertSame('English', $link->href());
  }

}
