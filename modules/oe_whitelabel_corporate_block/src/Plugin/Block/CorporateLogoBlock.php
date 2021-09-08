<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_corporate_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposes a block with EU/EC logo (Corporate Block).
 *
 * @Block(
 *  id = "whitelabel_logo_block",
 *  admin_label = @Translation("Corporate Logo Block"),
 *  category = @Translation("Blocks"),
 * )
 */
class CorporateLogoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'source_logo' => 'ec',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $sourceLogoOptions = [
      'eu' => $this->t('EU logo'),
      'ec' => $this->t('EC logo'),
    ];
    $form['source_logo'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo Source'),
      '#description' => $this->t('Please select the source for the logo.'),
      '#options' => $sourceLogoOptions,
      '#default_value' => $config['source_logo'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('source_logo', $form_state->getValue('source_logo'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // @todo Yet to be developed.
    $config = $this->getConfiguration();
    return [
      '#markup' => $this->t('Logo here'),
    ];
  }

}
