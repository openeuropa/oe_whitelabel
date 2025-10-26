<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_modal_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Bootstrap libraries are removed when oe_whitelabel is active.
 */
class ModalPageBootstrapLibraryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_helper',
    'oe_whitelabel_modal_page',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure modal_page to load Bootstrap from CDN.
    // The tests verify that loading these assets are always prevented when
    // oe_whitelabel is active, as they are already provided by oe_whitelabel.
    $this->config('modal_page.settings')
      ->set('load_bootstrap', TRUE)
      ->set('bootstrap_version', '5x')
      ->save();

    // Create a modal entity that will trigger library loading.
    $modal_storage = \Drupal::entityTypeManager()->getStorage('modal');
    $modal = $modal_storage->create([
      'id' => 'test_modal',
      'label' => 'Test Modal',
      'body' => [
        'value' => 'Test modal content',
        'format' => 'plain_text',
      ],
      'pages' => '/user/*',
      'auto_open' => TRUE,
      'published' => TRUE,
      'type' => 'page',
    ]);
    $modal->save();
  }

  /**
   * Tests Bootstrap library removal when oe_whitelabel is active theme.
   */
  public function testBootstrapLibraryRemoval(): void {
    // Visit the user page.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();

    // Check if modal class exists (modal is active on the page).
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Assert that Bootstrap libraries are NOT loaded.
    $this->assertStringNotContainsString('bootstrap.min.js', $html);
    $this->assertStringNotContainsString('bootstrap.min.css', $html);
  }

  /**
   * Tests Bootstrap library removal when oe_whitelabel is base theme.
   */
  public function testBootstrapLibraryRemovalSubTheme(): void {
    // Install and set the test sub-theme as default.
    \Drupal::service('theme_installer')->install(['oe_whitelabel_test_subtheme']);
    $this->config('system.theme')
      ->set('default', 'oe_whitelabel_test_subtheme')
      ->save();

    // Clear caches to ensure theme change takes effect.
    drupal_flush_all_caches();

    // Visit the user page.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();

    // Assert that Bootstrap libraries are NOT loaded even with a sub-theme.
    $this->assertStringNotContainsString('bootstrap.min.js', $html);
    $this->assertStringNotContainsString('bootstrap.min.css', $html);
  }

  /**
   * Tests that Bootstrap libraries are loaded when using a different theme.
   */
  public function testDefaultBootstrapLibraryLoad(): void {
    // Change to a different theme that is not oe_whitelabel.
    $this->config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Clear caches to ensure theme change takes effect.
    drupal_flush_all_caches();

    // Visit the user page.
    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();

    // Assert that Bootstrap libraries ARE loaded when not using oe_whitelabel.
    $this->assertStringContainsString('bootstrap.min.js', $html);
    $this->assertStringContainsString('bootstrap.min.css', $html);
  }

}
