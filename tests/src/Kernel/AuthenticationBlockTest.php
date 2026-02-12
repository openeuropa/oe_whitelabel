<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Session\UserSession;
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
    'ui_patterns_settings',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    $this->config('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();
  }

  /**
   * Tests the rendering of the authentication block.
   */
  public function testBlockRendering(): void {
    $crawler = $this->renderAuthenticationBlock();

    $this->assertAuthenticationIcon($crawler, 'person');
    $link = $crawler->filter('a');
    $this->assertSame('Log in', $link->text());
    $this->assertAuthenticationLinkClasses($link, FALSE);
  }

  /**
   * Tests the rendering of the authentication block for logged in users.
   */
  public function testBlockRenderingForLoggedInUser(): void {
    $current_user = \Drupal::currentUser();
    $original_account = $current_user->getAccount();
    $current_user->setAccount(new UserSession(['uid' => 2, 'name' => 'Authenticated user']));

    $crawler = $this->renderAuthenticationBlock();
    $this->assertAuthenticationIcon($crawler, 'person-check');
    $link = $crawler->filter('a');
    $this->assertSame('Log out', $link->text());
    $this->assertAuthenticationLinkClasses($link, TRUE);

    $current_user->setAccount($original_account);
  }

  /**
   * Renders the authentication block and returns a crawler for the markup.
   */
  protected function renderAuthenticationBlock(): Crawler {
    $block_entity_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $block_entity_storage->load('oe_whitelabel_eulogin');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);

    return new Crawler($render->__toString());
  }

  /**
   * Asserts the icon rendered within the authentication block.
   */
  protected function assertAuthenticationIcon(Crawler $crawler, string $icon_name): void {
    $actual = $crawler->filter('.oe-authentication');
    $this->assertCount(1, $actual);
    $icon = $actual->filter('svg');
    $this->assertCount(1, $icon);
    $icon_class = $icon->attr('class') ?? '';
    $this->assertStringContainsString('icon--fluid', $icon_class);
    $use = $icon->filter('use');
    $this->assertCount(1, $use);
    $href = $use->attr('xlink:href') ?? $use->attr('href');
    $expected = '/themes/contrib/oe_bootstrap_theme/assets/icons/bcl-default-icons.svg#' . $icon_name;
    $this->assertSame($expected, $href);
  }

  /**
   * Asserts the authentication link classes.
   */
  protected function assertAuthenticationLinkClasses(Crawler $link, bool $logged_in): void {
    $classes = $link->attr('class') ?? '';
    $this->assertStringContainsString('top-navigation-link', $classes);
    $this->assertStringContainsString('d-inline-flex', $classes);
    $this->assertStringContainsString('gap-2-5', $classes);
    if ($logged_in) {
      $this->assertStringContainsString('active', $classes);
    }
    else {
      $this->assertStringNotContainsString('active', $classes);
    }
  }

}
