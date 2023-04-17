<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_extra_project\Plugin\ExtraField\Display;

/**
 * Display overall budget.
 *
 * @ExtraFieldDisplay(
 *   id = "oe_whitelabel_extra_project_eu_contrib",
 *   label = @Translation("EU contribution"),
 *   bundles = {
 *     "node.oe_project",
 *   },
 *   visible = true
 * )
 */
class EuContributionExtraField extends BudgetExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('EU contribution');
  }

  /**
   * {@inheritdoc}
   */
  protected function getLegacyBudgetFieldName(): string {
    return 'oe_project_budget_eu';
  }

  /**
   * {@inheritdoc}
   */
  protected function getBudgetFieldName(): string {
    return 'oe_project_eu_contrib';
  }

}
