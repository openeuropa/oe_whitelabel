<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\DefaultWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * OpenEuropa custom better exposed filters widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "oe_whitelabel_float_end_sort",
 *   label = @Translation("Float End Sort"),
 * )
 */
class FloatEndSortWidget extends DefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);
    $form['#attributes']['class'][] = 'float-lg-end';
    $form['#attributes']['class'][] = 'd-none';
    $form['#attributes']['class'][] = 'd-md-flex';
    $form['#attributes']['class'][] = 'align-items-baseline';
  }

}
