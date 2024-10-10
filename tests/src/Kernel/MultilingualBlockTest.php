<?php

declare(strict_types=1);

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
    'language',
    'locale',
    'oe_bootstrap_theme_helper',
    'oe_multilingual',
    'oe_whitelabel_multilingual',
    'system',
    'ui_patterns',
    'ui_patterns_library',
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

    $this->container->set('theme.registry', NULL);

    $this->installSchema('locale', [
      'locales_location',
      'locales_target',
      'locales_source',
      'locale_file',
    ]);

    $this->installConfig([
      'locale',
      'language',
      'oe_multilingual',
    ]);

    $this->container->get('module_handler')->loadInclude('oe_multilingual', 'install');
    oe_multilingual_install(FALSE);

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->load('oe_whitelabel_language_switcher');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $block = $crawler->filter('div.language-switcher');
    $this->assertCount(1, $block);
    $link = $crawler->filter('div.language-switcher > a');
    $this->assertEquals('Change language. Current language is English.', $link->attr('aria-label'));
    $this->assertSame('English', trim($link->text()));
    $this->assertSame('#', $link->attr('href'));
    $title = $crawler->filter('div#languageModal .modal-title');
    $this->assertSame('Select your language', $title->text());
    $button_header = $crawler->filter('button.btn-close');
    $this->assertSame('modal', $button_header->attr('data-bs-dismiss'));

    foreach ($this->languageDataProvider() as $data) {
      $this->assertLanguageLink($crawler, $data['0'], $data['1']);
    }
  }

  /**
   * Asserts the language link rendering.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The crawler.
   * @param string $label
   *   The language label.
   * @param string $code
   *   The language code.
   */
  protected function assertLanguageLink(Crawler $crawler, string $label, string $code): void {
    $link_language = $crawler->filter('a#link_' . $code);
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame($label, $link_language->text());
  }

  /**
   * Provides data for the language links.
   *
   * @return \Generator
   *   The language links data.
   */
  protected function languageDataProvider(): \Generator {
    yield ['български', 'bg'];
    yield ['čeština', 'cs'];
    yield ['dansk', 'da'];
    yield ['Deutsch', 'de'];
    yield ['eesti', 'et'];
    yield ['ελληνικά', 'el'];
    yield ['English', 'en'];
    yield ['español', 'es'];
    yield ['français', 'fr'];
    yield ['Gaeilge', 'ga'];
    yield ['hrvatski', 'hr'];
    yield ['italiano', 'it'];
    yield ['lietuvių', 'lt'];
    yield ['latviešu', 'lv'];
    yield ['magyar', 'hu'];
    yield ['Malti', 'mt'];
    yield ['Nederlands', 'nl'];
    yield ['polski', 'pl'];
    yield ['português', 'pt-pt'];
    yield ['română', 'ro'];
    yield ['slovenčina', 'sk'];
    yield ['slovenščina', 'sl'];
    yield ['suomi', 'fi'];
    yield ['svenska', 'sv'];
  }

}
