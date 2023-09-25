<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the OE Authentication LoginBlock rendering.
 */
class AuthenticationBlockTest extends AbstractKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'cas',
    'externalauth',
    'oe_authentication',
  ];

  /**
   * Tests the rendering of the authentication block.
   */
  public function testBlockRendering(): void {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->load('oe_whitelabel_eulogin');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $actual = $crawler->filter('.oe-authentication');
    $this->assertCount(1, $actual);
    $icon = $actual->filter('svg');
    $this->assertSame('me-2-5 bi icon--xs', $icon->attr('class'));
    $use = $icon->filter('use');
    $expected = '/themes/contrib/oe_bootstrap_theme/assets/icons/bcl-default-icons.svg#person-fill';
    $this->assertSame($expected, $use->attr('xlink:href'));
    $link = $crawler->filter('a');
    $this->assertSame('Log in', $link->text());
  }

}
