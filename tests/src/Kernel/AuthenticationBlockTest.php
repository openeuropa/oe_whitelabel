<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the OE Authentication LoginBlock rendering.
 */
class AuthenticationBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'cas',
    'externalauth',
    'oe_authentication',
    'oe_bootstrap_theme_helper',
    'system',
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
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'euloginlinkblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_authentication_login_block',
      'settings' => [
        'id' => 'oe_authentication_login_block',
        'label' => 'EU Login Link Block',
        'provider' => 'oe_authentication',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('.oe-authentication');
    $this->assertCount(1, $actual);
    $icon = $actual->filter('svg');
    $this->assertSame('ms-2-5 bi icon--xs', $icon->attr('class'));
    $use = $icon->filter('use');
    $expected = '/themes/contrib/oe_bootstrap_theme/assets/icons/bootstrap-icons.svg#person-fill';
    $this->assertSame($expected, $use->attr('xlink:href'));
    $link = $crawler->filter('a');
    $this->assertSame('Log in', $link->text());
  }

}
