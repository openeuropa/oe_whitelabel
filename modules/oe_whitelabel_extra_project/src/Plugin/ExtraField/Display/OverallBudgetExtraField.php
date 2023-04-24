<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_extra_project\Plugin\ExtraField\Display;

/**
 * Display overall budget.
 *
 * @ExtraFieldDisplay(
 *   id = "oe_whitelabel_extra_project_project_budget",
 *   label = @Translation("Overall budget"),
 *   bundles = {
 *     "node.oe_project",
 *   },
 *   visible = true
 * )
 */
class OverallBudgetExtraField extends BudgetExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Overall budget');
  }

  /**
   * {@inheritdoc}
   */
  protected function getLegacyBudgetFieldName(): string {
    return 'oe_project_budget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getBudgetFieldName(): string {
    return 'oe_project_eu_budget';
  }

}
