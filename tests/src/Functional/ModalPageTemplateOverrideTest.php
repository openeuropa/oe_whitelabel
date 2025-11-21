<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests that modal configuration options render correctly through templates.
 */
class ModalPageTemplateOverrideTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_helper',
    'oe_whitelabel_modal_page',
    'filter',
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

    // Configure modal_page for Bootstrap 5.x to enable toast support.
    $this->config('modal_page.settings')
      ->set('modal_provider', 'bootstrap')
      ->set('bootstrap_version', '5x')
      ->save();

    // Create a basic HTML text format for testing formatted body content.
    $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <em> <a>',
          ],
        ],
      ],
    ]);
    $format->save();

    // Create a reusable test modal with default configuration.
    $modal_storage = \Drupal::entityTypeManager()->getStorage('modal');
    $modal = $modal_storage->create([
      'id' => 'test_modal',
      'label' => 'Test modal title',
      'body' => [
        'value' => 'Test modal body content.',
        'format' => 'plain_text',
      ],
      'pages' => '/user/login',
      'auto_open' => TRUE,
      'published' => TRUE,
      'type' => 'page',
    ]);
    $modal->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');
    if ($modal) {
      $modal->delete();
    }
    parent::tearDown();
  }

  /**
   * Tests modal rendering with standard configuration.
   */
  public function testModalRendering(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');
    // Header configuration.
    $modal->setEnableModalHeader(TRUE);
    $modal->setDisplayTitle(TRUE);
    $modal->setDisplayButtonXclose(TRUE);
    // Footer configuration.
    $modal->setEnableModalFooter(TRUE);
    $modal->setEnableLeftButton(TRUE);
    $modal->setLeftLabelButton('Cancel');
    $modal->setEnableRightButton(TRUE);
    $modal->setOkLabelButton('Confirm');
    // Size.
    $modal->setModalSize('modal-lg');
    $modal->save();

    // Visit the user login page to trigger the modal.
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();

    // Assert modal is present.
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Assert header and title are rendered.
    $this->assertStringContainsString('Test modal title', $html);

    // Assert close button is present.
    $this->assertStringContainsString('btn-close', $html);

    // Assert body content is rendered.
    $this->assertStringContainsString('Test modal body content.', $html);

    // Assert footer buttons are rendered.
    $this->assertStringContainsString('Cancel', $html);
    $this->assertStringContainsString('Confirm', $html);
    $this->assertStringContainsString('js-modal-page-ok-button', $html);

    // Default: horizontal lines enabled, modal-no-border should NOT be present.
    $crawler = new Crawler($html);
    $modal_header = $crawler->filter('.modal-header');
    $this->assertCount(1, $modal_header);
    $header_classes = $modal_header->attr('class') ?? '';
    $this->assertStringNotContainsString('modal-no-border', $header_classes);

    $modal_footer = $crawler->filter('.modal-footer');
    $this->assertCount(1, $modal_footer);
    $footer_classes = $modal_footer->attr('class') ?? '';
    $this->assertStringNotContainsString('modal-no-border', $footer_classes);

    // Test with horizontal lines disabled for both header and footer.
    $modal->setInsertHorizontalLineHeader(FALSE);
    $modal->setInsertHorizontalLineFooter(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    // Both header and footer should have modal-no-border class.
    $crawler = new Crawler($html);
    $modal_header = $crawler->filter('.modal-header');
    $this->assertCount(1, $modal_header);
    $header_classes = $modal_header->attr('class') ?? '';
    $this->assertStringContainsString('modal-no-border', $header_classes);

    $modal_footer = $crawler->filter('.modal-footer');
    $this->assertCount(1, $modal_footer);
    $footer_classes = $modal_footer->attr('class') ?? '';
    $this->assertStringContainsString('modal-no-border', $footer_classes);
  }

  /**
   * Tests modal body content variations.
   */
  public function testModalBodyContent(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test with body text only (already set in setUp).
    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Test modal body content.', $html);
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Test with video link.
    $modal->setModalVideoLink('https://www.youtube.com/watch?v=OkPW9mK5Vw8');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringContainsString('https://www.youtube.com/embed/OkPW9mK5Vw8', $html);
    $this->assertStringContainsString('ratio ratio-16x9', $html);

    // Test with formatted text (HTML).
    $modal->setBody([
      'value' => '<p>This is <strong>bold text</strong> and <em>italic text</em>.</p>',
      'format' => 'basic_html',
    ]);
    $modal->setModalVideoLink(NULL);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('<strong>bold text</strong>', $html);
    $this->assertStringContainsString('<em>italic text</em>', $html);
  }

  /**
   * Tests modal header and close button variations.
   */
  public function testModalHeaderVariations(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test header enabled with title and close button.
    $modal->setEnableModalHeader(TRUE);
    $modal->setDisplayTitle(TRUE);
    $modal->setDisplayButtonXclose(TRUE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Test modal title', $html);
    $this->assertStringContainsString('btn-close', $html);

    // Test header without close button.
    $modal->setEnableModalHeader(TRUE);
    $modal->setDisplayTitle(TRUE);
    $modal->setDisplayButtonXclose(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Test modal title', $html);
    $this->assertStringNotContainsString('btn-close', $html);

    // Test header without title.
    $modal->setEnableModalHeader(TRUE);
    $modal->setDisplayTitle(FALSE);
    $modal->setDisplayButtonXclose(TRUE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString('Test modal title', $html);
    $this->assertStringContainsString('btn-close', $html);

    // Test header disabled.
    $modal->setEnableModalHeader(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString('Test modal title', $html);
    $this->assertStringNotContainsString('btn-close', $html);
  }

  /**
   * Tests modal footer and button configurations.
   */
  public function testModalFooterButtons(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test footer with both buttons.
    $modal->setEnableModalFooter(TRUE);
    $modal->setEnableLeftButton(TRUE);
    $modal->setLeftLabelButton('Back');
    $modal->setEnableRightButton(TRUE);
    $modal->setOkLabelButton('Continue');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Back', $html);
    $this->assertStringContainsString('Continue', $html);

    // Test footer with left button only.
    $modal->setEnableModalFooter(TRUE);
    $modal->setEnableLeftButton(TRUE);
    $modal->setLeftLabelButton('Dismiss');
    $modal->setEnableRightButton(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Dismiss', $html);
    $this->assertStringNotContainsString('Back', $html);
    $this->assertStringNotContainsString('Continue', $html);

    // Test footer with right button only (with redirect link).
    $modal->setEnableModalFooter(TRUE);
    $modal->setEnableLeftButton(FALSE);
    $modal->setEnableRightButton(TRUE);
    $modal->setOkLabelButton('Accept');
    $modal->setEnableRedirectLink(TRUE);
    $modal->setRedirectLink('https://example.com');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Accept', $html);
    $this->assertStringNotContainsString('Back', $html);
    $this->assertStringNotContainsString('Dismiss', $html);
    // Verify redirect link is set on the button.
    $this->assertStringContainsString('data-redirect="https://example.com"', $html);

    // Test no footer.
    $modal->setEnableModalFooter(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    // Verify modal is present but without footer buttons.
    $this->assertStringContainsString('js-modal-page-show', $html);
    $this->assertStringNotContainsString('Back', $html);
    $this->assertStringNotContainsString('Dismiss', $html);
    $this->assertStringNotContainsString('Accept', $html);
  }

  /**
   * Tests modal size options.
   */
  public function testModalSizes(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test with large size.
    $modal->setModalSize('modal-lg');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    $this->assertStringContainsString('Test modal body content.', $html);

    // Test with small size.
    $modal->setModalSize('modal-sm');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    $this->assertStringContainsString('Test modal body content.', $html);
  }

  /**
   * Tests modal display behavior options (hidden fields).
   */
  public function testModalDisplayOptions(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test modal with default values (no special configuration).
    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Always-present hidden fields with default (empty) values.
    $this->assertStringContainsString('id="modal_id" name="modal_id" value="test_modal"', $html);
    $this->assertStringContainsString('id="delay_display" name="delay_display" value=""', $html);
    $this->assertStringContainsString('id="show_once" name="show_once" value=""', $html);
    $this->assertStringContainsString('id="auto_hide" name="auto_hide" value=""', $html);
    $this->assertStringContainsString('id="auto_hide_delay" name="auto_hide_delay" value=""', $html);

    // "Don't show again" checkbox is present by default.
    $this->assertStringContainsString('modal-dont-show-again-label', $html);
    $this->assertStringContainsString('modal-page-please-do-not-show-again', $html);
    $this->assertStringContainsString('id="cookie_expiration"', $html);
    // Verify default cookie expiration time (10000 days).
    $this->assertStringContainsString('id="cookie_expiration" name="cookie_expiration" value="10000"', $html);

    // Height offset should NOT be present by default.
    $this->assertStringNotContainsString('id="height_offset"', $html);

    // Test with "don't show again" checkbox disabled.
    $modal->setEnableDontShowAgainOption(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    // Checkbox and cookie expiration should NOT be present.
    $this->assertStringNotContainsString('modal-dont-show-again-label', $html);
    $this->assertStringNotContainsString('modal-page-please-do-not-show-again', $html);
    $this->assertStringNotContainsString('id="cookie_expiration"', $html);

    // Test modal with all display options configured.
    $modal->setDelayDisplay('3');
    $modal->setShowModalOnlyOnce(TRUE);
    $modal->setModalAutoHide(TRUE);
    $modal->setModalAutoHideDelay('5');
    $modal->setEnableShowOnHeight(TRUE);
    $modal->setHeightOffset('20%');
    $modal->setHeightOffsetTouch('0.5');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Verify all hidden fields have configured values.
    $this->assertStringContainsString('id="modal_id" name="modal_id" value="test_modal"', $html);
    $this->assertStringContainsString('id="delay_display" name="delay_display" value="3"', $html);
    $this->assertStringContainsString('id="show_once" name="show_once" value="1"', $html);
    $this->assertStringContainsString('id="auto_hide" name="auto_hide" value="1"', $html);
    $this->assertStringContainsString('id="auto_hide_delay" name="auto_hide_delay" value="5"', $html);

    // Verify height offset field with correct value and offset-type attribute.
    $this->assertStringContainsString('id="height_offset" name="height_offset" value="20%"', $html);
    $this->assertStringContainsString('offset-type="0.5"', $html);
  }

  /**
   * Tests modal interaction behavior options.
   */
  public function testModalInteractionBehavior(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    // Test default settings (ESC key enabled, click outside closes).
    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    // Default: ESC key closes modal (data-bs-keyboard="true").
    $this->assertStringContainsString('data-bs-keyboard="true"', $html);
    // Default: clicking outside closes modal (no static backdrop).
    $this->assertStringNotContainsString('data-bs-backdrop="static"', $html);

    // Test with ESC key disabled and static backdrop (click outside disabled).
    $modal->setCloseModalEscKey(FALSE);
    $modal->setCloseModalClickingOutside(FALSE);
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('js-modal-page-show', $html);
    // ESC key disabled (data-bs-keyboard="false").
    $this->assertStringContainsString('data-bs-keyboard="false"', $html);
    // Static backdrop enabled (data-bs-backdrop="static").
    $this->assertStringContainsString('data-bs-backdrop="static"', $html);
  }

  /**
   * Tests toast configuration rendering.
   */
  public function testToastRendering(): void {
    /** @var \Drupal\modal_page\Entity\ModalInterface $modal */
    $modal = \Drupal::entityTypeManager()->getStorage('modal')->load('test_modal');

    $modal->setDisplayAsToast(TRUE);
    $modal->setEnableModalHeader(TRUE);
    $modal->setDisplayTitle(TRUE);
    $modal->setDisplayButtonXclose(TRUE);
    $modal->setEnableModalFooter(TRUE);
    $modal->setEnableRightButton(TRUE);
    $modal->setOkLabelButton('Dismiss');
    $modal->setModalAutoHide(TRUE);
    $modal->setModalAutoHideDelay('5');
    $modal->save();

    $this->drupalGet('user/login');
    $html = $this->getSession()->getPage()->getContent();

    // Assert toast-specific attributes are present.
    $this->assertStringContainsString('data-is-toast', $html);
    $this->assertStringContainsString('js-modal-page-show', $html);

    // Assert toast content is rendered.
    $this->assertStringContainsString('Test modal body content.', $html);
    $this->assertStringContainsString('Test modal title', $html);

    // Assert footer button is rendered.
    $this->assertStringContainsString('Dismiss', $html);

    // Assert auto-hide delay attribute.
    // The template multiplies delay by 1000 for milliseconds.
    $this->assertStringContainsString('data-bs-delay', $html);
  }

}
