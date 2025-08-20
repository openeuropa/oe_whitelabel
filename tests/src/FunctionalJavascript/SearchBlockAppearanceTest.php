<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the appearance and open/close behavior of the search block.
 *
 * This does not test the actual search behavior, only the theming.
 */
class SearchBlockAppearanceTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_helper',
    'oe_whitelabel_search',
  ];

  /**
   * Tests the search block.
   */
  public function testSearchBlock(): void {
    $this->doTestSearchBlockInHeaderTop();
    $this->doTestSearchBlockInOtherRegions();
  }

  /**
   * Tests the search block in the header_top region.
   */
  protected function doTestSearchBlockInHeaderTop(): void {
    $assert_session = $this->assertSession();

    // Move the block to the 'header_top' theme region.
    $this->config('block.block.oe_whitelabel_search_form')
      ->set('region', 'header_top')
      ->set('settings.form.region', 'header_top')
      ->save();
    $this->drupalGet('');

    // Assert that the search block and all its elements appear exactly once.
    $assert_session->elementsCount('css', '.search-dropdown', 1);
    $assert_session->elementsCount('css', '.search-dropdown .dropdown-toggle', 1);
    $assert_session->elementsCount('css', '.search-dropdown .dropdown-menu', 1);
    $assert_session->elementsCount('css', 'form[id^=oe-whitelabel-search-form]', 1);
    $assert_session->elementsCount('css', '[name=search_input]', 1);
    $assert_session->elementsCount('css', '.nav > .search-dropdown > .dropdown-menu > form#oe-whitelabel-search-form > [name=search_input]', 1);

    $toggle_button = $assert_session->elementExists('css', '.nav > .search-dropdown > .dropdown-toggle');
    $search_dropdown = $assert_session->elementExists('css', '.nav > .search-dropdown > .dropdown-menu');

    // In wide viewport, the toggle button is hidden, and the dropdown is
    // positioned inside its parent.
    $this->getSession()->resizeWindow(1400, 800);
    $this->assertFalse($toggle_button->isVisible());
    $this->assertTrue($search_dropdown->isVisible());
    $this->assertSameBoundingClientRect('.search-dropdown', '.search-dropdown > .dropdown-menu');
    $this->assertSameBoundingClientRect('.search-dropdown', '#oe-whitelabel-search-form');
    $this->assertSameBoundingClientRect('.search-dropdown', '.nav', ['left', 'width']);
    $form_rect_wide_viewport = $this->getBoundingClientRect('#oe-whitelabel-search-form');

    // Resize to medium viewport.
    // The search form is hidden, but the toggle button appears.
    $this->getSession()->resizeWindow(1199, 800);
    $this->assertTrue($toggle_button->isVisible());
    $this->assertFalse($search_dropdown->isVisible());
    // The toggle button is positioned inside the block.
    $this->assertSameBoundingClientRect('.search-dropdown', '.search-dropdown > .dropdown-toggle');
    $this->assertSameBoundingClientRect('.search-dropdown', '.nav', ['left', 'width']);
    $block_rect = $this->getBoundingClientRect('.search-dropdown');

    // Click to reveal the search form.
    $toggle_button->click();
    $this->assertTrue($toggle_button->isVisible());
    $this->assertTrue($search_dropdown->isVisible());
    // The block position and size does not change.
    $this->assertSame($block_rect, $this->getBoundingClientRect('.search-dropdown'));
    // The search form appears below the top nav region.
    $nav_rect = $this->getBoundingClientRect('.nav');
    $dropdown_rect = $this->getBoundingClientRect('.search-dropdown > .dropdown-menu');
    $this->assertSame($nav_rect['right'], $dropdown_rect['right']);
    $this->assertGreaterThan($nav_rect['bottom'], $dropdown_rect['top']);
    $this->assertLessThan($nav_rect['bottom'] + 3, $dropdown_rect['top']);

    // Click again to hide the search form.
    $toggle_button->click();
    $this->assertTrue($toggle_button->isVisible());
    $this->assertFalse($search_dropdown->isVisible());

    // Click again to reveal the search form.
    $toggle_button->click();
    $this->assertTrue($toggle_button->isVisible());
    $this->assertTrue($search_dropdown->isVisible());

    // Back to wide viewport.
    // The open/close state has no effect on the appearance in wide viewport.
    $this->getSession()->resizeWindow(1400, 800);
    $this->assertFalse($toggle_button->isVisible());
    $this->assertTrue($search_dropdown->isVisible());
    $this->assertSame($form_rect_wide_viewport, $this->getBoundingClientRect('#oe-whitelabel-search-form'));

    // Resize to a narrower viewport.
    // The search form is still revealed.
    $this->getSession()->resizeWindow(1199, 800);
    $this->assertTrue($toggle_button->isVisible());
    $this->assertTrue($search_dropdown->isVisible());
  }

  /**
   * Tests the search block when placed in other theme regions.
   */
  protected function doTestSearchBlockInOtherRegions(): void {
    $assert_session = $this->assertSession();
    $this->getSession()->resizeWindow(1250, 800);

    // Move the block to the 'header' theme region.
    $this->config('block.block.oe_whitelabel_search_form')
      ->set('region', 'header')
      ->set('settings.form.region', 'header')
      ->save();
    $this->drupalGet('');
    // The search form is not wrapped in '.search-dropdown'.
    $assert_session->elementsCount('css', '.search-dropdown', 0);
    $assert_session->elementsCount('css', '.bcl-header > .bg-lighter > .container > .row > .col-12 > form#oe-whitelabel-search-form.bcl-search-form > .bcl-search-form__group > [name=search_input]', 1);

    // Move the block to the 'navigation_right' theme region.
    $this->config('block.block.oe_whitelabel_search_form')
      ->set('region', 'navigation_right')
      ->set('settings.form.region', 'navigation_right')
      ->save();
    $this->drupalGet('');
    // The search form is not wrapped in '.search-dropdown'.
    $assert_session->elementsCount('css', '.search-dropdown', 0);
    $assert_session->elementsCount('css', '#bcl-navbar--2 > form#oe-whitelabel-search-form.d-flex.mt-3.mt-lg-0 > [name=search_input]', 1);
  }

  /**
   * Asserts that two elements have the same position and size.
   *
   * @param string $selector
   *   CSS selector for the first element.
   * @param string $other_selector
   *   CSS selector for the second element.
   * @param list<'left'|'right'|'width'|'top'|'bottom'|'height'> $ignore_keys
   *   Keys of the bounding rectangle that are expected to be different.
   */
  protected function assertSameBoundingClientRect(string $selector, string $other_selector, array $ignore_keys = []): void {
    $get_rectangle = function (string $selector) use ($ignore_keys) {
      $rect = $this->getBoundingClientRect($selector);
      $rect = array_diff_key($rect, array_fill_keys($ignore_keys, TRUE));
      return $rect;
    };
    $this->assertSame(
      $get_rectangle($selector),
      $get_rectangle($other_selector),
    );
  }

  /**
   * Gets the position of an element.
   *
   * @param string $selector
   *   CSS selector for the element.
   *
   * @return array<'left'|'right'|'width'|'top'|'bottom'|'height', float|int>
   *   Bounding rectangle for the element relative to the viewport.
   */
  protected function getBoundingClientRect(string $selector): array {
    $rect = $this->getSession()->evaluateScript(sprintf("document.querySelector(%s).getBoundingClientRect()", json_encode($selector)));
    // The values for 'x' and 'y' are the same as for 'left' and 'top'.
    unset($rect['x'], $rect['y']);
    return $rect;
  }

}
