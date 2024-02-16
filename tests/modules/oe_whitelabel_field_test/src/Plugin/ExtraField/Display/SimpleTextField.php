<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_field_test\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;

/**
 * A simple field that shows a string.
 *
 * @ExtraFieldDisplay(
 *   id = "oe_wt_field_test_string",
 *   label = @Translation("Simple string field"),
 *   description = @Translation("A simple field that shows a string."),
 *   bundles = {
 *     "node.*",
 *   }
 * )
 */
class SimpleTextField extends ExtraFieldDisplayFormattedBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    return [
      '#plain_text' => 'The OpenEuropa Initiative.',
    ];
  }

}
