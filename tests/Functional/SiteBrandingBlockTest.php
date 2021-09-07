<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WebAssert;

/**
 * Tests of Site Branding Block.
 */
class SiteBrandingBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'config',
    'system',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
    'components',
    'field_ui',
    'toolbar',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable and set OpenEuropa Whitelabel Theme as default.
    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    /* Rebuild the ui_pattern definitions to collect the ones provided by
    oe_whitelabel itself. */
    \Drupal::service('plugin.manager.ui_patterns')->clearCachedDefinitions();

    // Ensure that the system breadcrumb is placed as well.
    $this->drupalPlaceBlock('osystem_branding_block', [
      'region' => 'header',
    ]);

  }

  /**
   * Asserts classes contained at site branding block.
   */
  public function testClassesSiteName(): void {
    ini_set('xdebug.var_display_max_depth', '10');
    ini_set('xdebug.var_display_max_children', '256');
    ini_set('xdebug.var_display_max_data', '10024');
    $this->drupalGet('<front>');
    $assert_session = $this->assertSession();

    $webAssert = new WebAssert($this->getSession());
    $element = $webAssert->elementExists('css', 'html');
    $actual = $element->getHtml();
    var_dump($actual);

    $assert_session->elementExists('css', 'div.site-name.h1.text-white.text-decoration-none.align-bottom');

  }

}
