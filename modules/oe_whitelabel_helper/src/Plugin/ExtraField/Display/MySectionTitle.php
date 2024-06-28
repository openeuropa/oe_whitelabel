<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;

/**
 * Displays a section title if the relative fields are not empty.
 *
 * @ExtraFieldDisplay(
 *   id = "oe_whitelabel_extra_section_title",
 *   label = @Translation("My section title"),
 *   bundles = {
 *     "node.page",
 *   },
 *   visible = true
 * )
 */
class MySectionTitle extends ExtraFieldDisplayFormattedBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    if ($entity->get('field_one')->isEmpty() && $entity->get('field_multi')->isEmpty()) {
      return [];
    }

    return [
      '#markup' => $this->t('My section title'),
    ];
  }

}
