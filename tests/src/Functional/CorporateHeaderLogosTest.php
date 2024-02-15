<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that corporate logos are displayed correctly.
 */
class CorporateHeaderLogosTest extends WhitelabelBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'oe_whitelabel_helper',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('module_installer')->install(['language']);
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();

    // Rebuild the ui_pattern definitions to collect the ones provided by
    // oe_whitelabel itself.
    \Drupal::service('plugin.manager.ui_patterns')->clearCachedDefinitions();
  }

  /**
   * Tests the header logos.
   */
  public function testCorporateHeaderLogos(): void {
    // Create a user that has permission to administer theme settings.
    $user = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($user);

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->elementExists('css', 'header.bcl-header.bcl-header--neutral');
    $assert_session->elementExists('css', 'img[alt="Home logo"]');

    // Select EU component library and save configuration.
    $this->drupalGet('/admin/appearance/settings/oe_whitelabel');
    $page->selectFieldOption('Component library', 'European Union');
    $page->pressButton('Save configuration');

    $this->drupalGet('<front>');

    $assert_session->elementExists('css', 'header.bcl-header.bcl-header--eu');
    $assert_session->elementExists('css', 'header > nav > div > a[href="https://european-union.europa.eu/index_en"]');
    $assert_session->elementExists('css', 'picture > source[srcset="/build/themes/contrib/oe_bootstrap_theme/assets/logos/eu/mobile/logo-eu--en.svg"]');
    $assert_session->elementExists('css', 'picture > img[src="/build/themes/contrib/oe_bootstrap_theme/assets/logos/eu/logo-eu--en.svg"]');

    $this->drupalGet('es/');

    $assert_session->elementExists('css', 'header.bcl-header.bcl-header--eu');
    $assert_session->elementExists('css', 'header > nav > div > a[href="https://european-union.europa.eu/index_es"]');
    $assert_session->elementExists('css', 'picture > source[srcset="/build/themes/contrib/oe_bootstrap_theme/assets/logos/eu/mobile/logo-eu--es.svg"]');
    $assert_session->elementExists('css', 'picture > img[src="/build/themes/contrib/oe_bootstrap_theme/assets/logos/eu/logo-eu--es.svg"]');

    // Select EC component library and save configuration.
    $this->drupalGet('/admin/appearance/settings/oe_whitelabel');
    $page->selectFieldOption('Component library', 'European Commission');
    $page->pressButton('Save configuration');

    $this->drupalGet('<front>');
    $assert_session->elementExists('css', 'header.bcl-header.bcl-header--ec');
    $assert_session->elementExists('css', 'header > nav > div > a[href="https://ec.europa.eu/info/index_en"]');
    $assert_session->elementExists('css', 'img[src="/build/themes/contrib/oe_bootstrap_theme/assets/logos/ec/logo-ec--en.svg"]');

    $this->drupalGet('es/');
    $assert_session->elementExists('css', 'header.bcl-header.bcl-header--ec');
    $assert_session->elementExists('css', 'header > nav > div > a[href="https://ec.europa.eu/info/index_es"]');
    $assert_session->elementExists('css', 'img[src="/build/themes/contrib/oe_bootstrap_theme/assets/logos/ec/logo-ec--es.svg"]');
  }

}
