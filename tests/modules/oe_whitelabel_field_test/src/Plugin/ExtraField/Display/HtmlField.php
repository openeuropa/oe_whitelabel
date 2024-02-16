<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_field_test\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;

/**
 * A field that shows multiple deltas with HTML strings.
 *
 * @ExtraFieldDisplay(
 *   id = "oe_wt_field_test_html_multiple",
 *   label = @Translation("Multiple deltas HTML strings"),
 *   description = @Translation("A field that shows multiple deltas with HTML strings."),
 *   bundles = {
 *     "node.*",
 *   }
 * )
 */
class HtmlField extends ExtraFieldDisplayFormattedBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    return [
      ['#markup' => '<span>First line with <b>markup</b>.</span>'],
      ['#markup' => '<span>Second line with <b>markup</b>.</span>'],
    ];
  }

}
