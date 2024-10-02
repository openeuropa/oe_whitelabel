<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Traits;

/**
 * Contains methods to assert the presence of specific blocks and elements.
 */
trait CckContainerTrait {

  /**
   * Asserts the HTML of the "cck_here" container.
   */
  protected function assertCckContainer(): void {
    $cck_container = $this->cssSelect('#cck_here')[0];
    $this->assertNotNull($cck_container);

    $expected_html = '<div id="cck_here" role="alert"></div>';
    $this->assertEquals($expected_html, $cck_container->getOuterHtml());
  }

}
