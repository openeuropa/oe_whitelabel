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
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
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
    $title = $crawler->filter('div#languageModal h5');
    $this->assertSame('Select your language', $title->text());
    $button_header = $crawler->filter('button.btn-close');
    $this->assertSame('modal', $button_header->attr('data-bs-dismiss'));
    $link_language = $crawler->filter('a#link_bg');
    $this->assertSame('http://localhost/bg/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('български', $link_language->text());
    $link_language = $crawler->filter('a#link_cs');
    $this->assertSame('http://localhost/cs/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('čeština', $link_language->text());
    $link_language = $crawler->filter('a#link_da');
    $this->assertSame('http://localhost/da/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('dansk', $link_language->text());
    $link_language = $crawler->filter('a#link_de');
    $this->assertSame('http://localhost/de/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('Deutsch', $link_language->text());
    $link_language = $crawler->filter('a#link_et');
    $this->assertSame('http://localhost/et/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('eesti', $link_language->text());
    $link_language = $crawler->filter('a#link_el');
    $this->assertSame('http://localhost/el/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('ελληνικά', $link_language->text());
    $link_language = $crawler->filter('a#link_en');
    $this->assertSame('http://localhost/en/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('English', $link_language->text());
    $link_language = $crawler->filter('a#link_es');
    $this->assertSame('http://localhost/es/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('español', $link_language->text());
    $link_language = $crawler->filter('a#link_fr');
    $this->assertSame('http://localhost/fr/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('français', $link_language->text());
    $link_language = $crawler->filter('a#link_ga');
    $this->assertSame('http://localhost/ga/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('Gaeilge', $link_language->text());
    $link_language = $crawler->filter('a#link_hr');
    $this->assertSame('http://localhost/hr/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('hrvatski', $link_language->text());
    $link_language = $crawler->filter('a#link_it');
    $this->assertSame('http://localhost/it/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('italiano', $link_language->text());
    $link_language = $crawler->filter('a#link_lt');
    $this->assertSame('http://localhost/lt/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('lietuvių', $link_language->text());
    $link_language = $crawler->filter('a#link_lv');
    $this->assertSame('http://localhost/lv/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('latviešu', $link_language->text());
    $link_language = $crawler->filter('a#link_hu');
    $this->assertSame('http://localhost/hu/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('magyar', $link_language->text());
    $link_language = $crawler->filter('a#link_mt');
    $this->assertSame('http://localhost/mt/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('Malti', $link_language->text());
    $link_language = $crawler->filter('a#link_nl');
    $this->assertSame('http://localhost/nl/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('Nederlands', $link_language->text());
    $link_language = $crawler->filter('a#link_pl');
    $this->assertSame('http://localhost/pl/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('polski', $link_language->text());
    $link_language = $crawler->filter('a#link_pt-pt');
    $this->assertSame('http://localhost/pt/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('português', $link_language->text());
    $link_language = $crawler->filter('a#link_ro');
    $this->assertSame('http://localhost/ro/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('română', $link_language->text());
    $link_language = $crawler->filter('a#link_sk');
    $this->assertSame('http://localhost/sk/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('slovenčina', $link_language->text());
    $link_language = $crawler->filter('a#link_sl');
    $this->assertSame('http://localhost/sl/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('slovenščina', $link_language->text());
    $link_language = $crawler->filter('a#link_fi');
    $this->assertSame('http://localhost/fi/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('suomi', $link_language->text());
    $link_language = $crawler->filter('a#link_sv');
    $this->assertSame('http://localhost/sv/%3Cnone%3E', $link_language->attr('href'));
    $this->assertSame('svenska', $link_language->text());
  }

}
