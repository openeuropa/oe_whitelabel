<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

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
   * Tests Bootstrap library loading across theme variants in one run.
   */
  public function testBootstrapLibraryLoadingVariants(): void {
    $assert_session = $this->assertSession();

    // Active oe_whitelabel theme should block Bootstrap.
    $this->drupalGet('user');
    $assert_session->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    $this->assertBootstrapLoaded($html, FALSE);

    // Sub-theme of oe_whitelabel should also block Bootstrap.
    \Drupal::service('theme_installer')->install(['oe_whitelabel_test_subtheme']);
    $this->config('system.theme')
      ->set('default', 'oe_whitelabel_test_subtheme')
      ->save();
    drupal_flush_all_caches();
    $this->drupalGet('user');
    $assert_session->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();
    $this->assertBootstrapLoaded($html, FALSE);

    // Non oe_whitelabel theme should allow Bootstrap.
    $this->config('system.theme')
      ->set('default', 'stark')
      ->save();
    drupal_flush_all_caches();
    $this->drupalGet('user');
    $assert_session->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();
    $this->assertBootstrapLoaded($html, TRUE);
  }

  /**
   * Asserts whether Bootstrap assets should be present.
   */
  protected function assertBootstrapLoaded(string $html, bool $should_load): void {
    if ($should_load) {
      $this->assertStringContainsString('bootstrap.min.js', $html);
      $this->assertStringContainsString('bootstrap.min.css', $html);
      return;
    }

    $this->assertStringNotContainsString('bootstrap.min.js', $html);
    $this->assertStringNotContainsString('bootstrap.min.css', $html);
  }

}
