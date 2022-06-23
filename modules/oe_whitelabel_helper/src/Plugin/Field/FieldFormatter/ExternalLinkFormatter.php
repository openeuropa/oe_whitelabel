<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'external_link' formatter.
 *
 * @FieldFormatter(
 *   id = "external_link",
 *   label = @Translation("Force external links to open in new tab"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class ExternalLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $url = $this->buildUrl($item);

      if ($url->isExternal()) {
        $element[$delta]['#options']['attributes']['target'] = '_blank';
      }
    }

    return $element;
  }

}
