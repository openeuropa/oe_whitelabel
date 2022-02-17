<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Corporate Logo Block rendering.
 */
class CorporateLogoBlockTest extends KernelTestBase {

  /**
   * Languages.
   *
   * @var array
   */
  protected $languages = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'oe_bootstrap_theme_helper',
    'oe_whitelabel_helper',
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

    $this->installConfig(['language']);
    $this->installEntitySchema('configurable_language');

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();
    $this->container->set('theme.registry', NULL);
  }

  /**
   * Tests the rendering of blocks.
   *
   * @param string $lang
   *   Language code.
   *
   * @dataProvider providerTestLogoBlockRendering
   */
  public function testLogoBlockRendering(string $lang): void {
    if ($lang !== 'en') {
      $this->setLanguageByCode($lang);
    }
    // Assert EC logo.
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'sitebranding',
      'theme' => 'oe_whitelabel',
      'plugin' => 'system_branding_block',
      'settings' => [
        'id' => 'system_branding_block',
        'label' => 'Site Branding',
        'provider' => 'system',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $logo = $crawler->filter('img');
    $this->assertCount(1, $logo);
    $expected = "/themes/contrib/oe_bootstrap_theme/assets/logos/ec/logo-ec--{$lang}.svg";
    $this->assertSame($expected, $logo->attr('src'));

    // Assert EU logo.
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'sitebranding',
      'theme' => 'oe_whitelabel',
      'plugin' => 'system_branding_block',
      'settings' => [
        'id' => 'system_branding_block',
        'label' => 'Corporate Logo Block',
        'provider' => 'system',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    $logo = $crawler->filter('img');
    $this->assertCount(1, $logo);
    $expected = "/themes/contrib/oe_bootstrap_theme/assets/logos/eu/logo-eu--{$lang}.svg";
    $this->assertSame($expected, $logo->attr('src'));
    $picture = $crawler->filter('picture');
    $this->assertCount(1, $picture);
    $source = $crawler->filter('source');
    $expected = "/themes/contrib/oe_bootstrap_theme/assets/logos/eu/mobile/logo-eu--{$lang}.svg";
    $this->assertSame($expected, $source->attr('srcset'));
  }

  /**
   * Sets language from lang code.
   *
   * @param string $language_code
   *   The language code.
   */
  protected function setLanguageByCode(string $language_code) :void {
    $this->languages[$language_code] = ConfigurableLanguage::createFromLangcode($language_code);
    $this->languages[$language_code]->save();
    $this->languageManager = $this->container->get('language_manager');
    $this->languageManager->reset();

    \Drupal::service('language.default')->set($this->languages[$language_code]);
  }

  /**
   * Provides test data for ::providerTestLogoBlockRendering().
   *
   * @return array[]
   *   Test data.
   */
  public function providerTestLogoBlockRendering(): array {
    return [
      'default' => [
        'en',
      ],
      'german' => [
        'de',
      ],
    ];
  }

}
