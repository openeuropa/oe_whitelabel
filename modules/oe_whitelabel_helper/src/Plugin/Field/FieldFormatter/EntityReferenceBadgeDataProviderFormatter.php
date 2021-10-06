<?php


namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "reference_badge_data_provider",
 *   label = @Translation("Badge data provider"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceBadgeDataProviderFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    foreach ($items as $delta => $item) {
      $entity = $item->get('entity')->getTarget()->getValue();
      $elements[$delta] = [
        '#type' => 'pattern',
        '#id' => 'badge',
        '#fields' => [
          'label' => $entity->label(),
          // @TODO: Set these values in config form.
           'attributes' => [
             'class' => ['me-2'],
           ],
        ],
      ];
    };

    return $elements;
  }

}
