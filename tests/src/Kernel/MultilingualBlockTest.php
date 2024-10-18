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
    'content_translation',
    'ctools',
    'language',
    'locale',
    'oe_bootstrap_theme_helper',
    'oe_multilingual',
    'oe_whitelabel_multilingual',
    'path',
    'path_alias',
    'pathauto',
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

    $this->installSchema('user', ['users_data']);

    $this->installConfig([
      'locale',
      'language',
      'content_translation',
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
    $this->assertSame('English', trim($link->text()));
    $this->assertSame('#', $link->attr('href'));
    $this->assertSame('Change language. Current language is English.', $link->attr('aria-label'));
    $title = $crawler->filter('div#languageModal h5');
    $this->assertSame('Select your language', $title->text());
    $button_header = $crawler->filter('button.btn-close');
    $this->assertSame('modal', $button_header->attr('data-bs-dismiss'));
    $link_language = $crawler->filter('a#link_bg');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('български', $link_language->text());
    $link_language = $crawler->filter('a#link_cs');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('čeština', $link_language->text());
    $link_language = $crawler->filter('a#link_da');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('dansk', $link_language->text());
    $link_language = $crawler->filter('a#link_de');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('Deutsch', $link_language->text());
    $link_language = $crawler->filter('a#link_et');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('eesti', $link_language->text());
    $link_language = $crawler->filter('a#link_el');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('ελληνικά', $link_language->text());
    $link_language = $crawler->filter('a#link_en');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('English', $link_language->text());
    $link_language = $crawler->filter('a#link_es');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('español', $link_language->text());
    $link_language = $crawler->filter('a#link_fr');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('français', $link_language->text());
    $link_language = $crawler->filter('a#link_ga');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('Gaeilge', $link_language->text());
    $link_language = $crawler->filter('a#link_hr');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('hrvatski', $link_language->text());
    $link_language = $crawler->filter('a#link_it');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('italiano', $link_language->text());
    $link_language = $crawler->filter('a#link_lt');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('lietuvių', $link_language->text());
    $link_language = $crawler->filter('a#link_lv');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('latviešu', $link_language->text());
    $link_language = $crawler->filter('a#link_hu');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('magyar', $link_language->text());
    $link_language = $crawler->filter('a#link_mt');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('Malti', $link_language->text());
    $link_language = $crawler->filter('a#link_nl');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('Nederlands', $link_language->text());
    $link_language = $crawler->filter('a#link_pl');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('polski', $link_language->text());
    $link_language = $crawler->filter('a#link_pt-pt');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('português', $link_language->text());
    $link_language = $crawler->filter('a#link_ro');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('română', $link_language->text());
    $link_language = $crawler->filter('a#link_sk');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('slovenčina', $link_language->text());
    $link_language = $crawler->filter('a#link_sl');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('slovenščina', $link_language->text());
    $link_language = $crawler->filter('a#link_fi');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('suomi', $link_language->text());
    $link_language = $crawler->filter('a#link_sv');
    $this->assertEmpty($link_language->attr('href'));
    $this->assertSame('svenska', $link_language->text());
  }

}
