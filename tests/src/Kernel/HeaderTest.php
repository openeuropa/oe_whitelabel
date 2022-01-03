<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the page Header rendering.
 */
class HeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
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

    $this->config('oe_whitelabel.settings')
      ->set('component_library', 'eu')
      ->set('header_style', 'standard')
      ->save();
  }

  /**
   * Tests the rendering of the EU header variant.
   */
  public function testEuHeaderRendering(): void {
    $crawler = new Crawler($this->renderPage());
    $header = $crawler->filter('header.bcl-header');
    $this->assertStringNotContainsString('ec__header', $header->attr('class'));
    $this->assertHeaderStructure($header);
  }

  /**
   * Tests the rendering of the EC header variant.
   */
  public function testEcHeaderRendering(): void {
    $this->config('oe_whitelabel.settings')
      ->set('component_library', 'ec')
      ->save();

    $crawler = new Crawler($this->renderPage());
    $header = $crawler->filter('header.ec__header.bcl-header');
    $this->assertHeaderStructure($header);
  }

  /**
   * Tests the rendering header with "light" style.
   */
  public function testHeaderLightRendering(): void {
    $this->config('oe_whitelabel.settings')
      ->set('header_style', 'light')
      ->save();

    $crawler = new Crawler($this->renderPage());
    $header = $crawler->filter('header.bcl-header');
    $this->assertHeaderStructure($header);
    $project = $header->filter('div.bcl-header__project');
    $this->assertSame('bcl-header__project light', $project->attr('class'));
  }

  /**
   * Renders the page template and returns the output.
   *
   * @return string
   *   The page template output.
   */
  protected function renderPage(): string {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    return (string) $renderer->executeInRenderContext(new RenderContext(), function () {
      return $this->container->get('theme.manager')->render('page', [
        'page' => [
          'navbar_branding' => 'Branding',
          'navbar_right' => 'Navbar-right',
          'header_top' => 'Header-top',
          'header_left' => 'Header-left',
          'header_right' => 'Header-right',
          'breadcrumbs' => 'Breadcrumbs',
        ],
      ]);
    });
  }

  /**
   * Asserts the general structure of the header.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $header
   *   The header node.
   */
  protected function assertHeaderStructure(Crawler $header): void {
    $this->assertCount(1, $header);
    $nav = $header->filter('nav.w-100.shadow-sm.navbar.navbar-expand-lg.navbar-light');
    $this->assertCount(1, $nav);
    $container = $nav->filter('div.container');
    $this->assertCount(1, $container);
    $this->assertStringContainsString('Branding', $container->text());
    $nav = $container->filter('ul.nav');
    $this->assertCount(1, $nav);
    $project = $header->filter('div.bcl-header__project');
    $this->assertCount(1, $project);
    $container = $project->filter('div.container');
    $this->assertStringContainsString('Header-top', $container->text());
    $nav = $header->filter('nav.bcl-header__navbar.navbar.navbar-expand-lg.navbar-dark');
    $this->assertCount(1, $nav);
    $container = $nav->filter('div.container');
    $this->assertCount(1, $container);
    $button = $container->filter('button.navbar-toggler');
    $this->assertSame('button', $button->attr('type'));
    $this->assertSame('collapse', $button->attr('data-bs-toggle'));
    $this->assertSame('#navbarNavDropdown', $button->attr('data-bs-target'));
    $this->assertSame('navbarNavDropdown', $button->attr('aria-controls'));
    $this->assertSame('false', $button->attr('aria-expanded'));
    $this->assertSame('Toggle navigation', $button->attr('aria-label'));
    $icon = $button->filter('span.navbar-toggler-icon');
    $this->assertCount(1, $icon);
    $collapse = $container->filter('div#navbarNavDropdown');
    $this->assertSame('collapse navbar-collapse', $collapse->attr('class'));
    $navbar = $collapse->filter('div.me-auto.navbar-nav');
    $this->assertCount(1, $navbar);
    $this->assertStringContainsString('Header-left', $navbar->text());
    $this->assertStringContainsString('Header-right', $collapse->text());
    $this->assertStringContainsString('Breadcrumbs', $header->text());
  }

}
