<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_corporate_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;

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
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['languages:language_interface']);

    $config = $this->getConfiguration();
    $cache->addCacheableDependency($config);

    $build['#theme'] = 'oe_whitelabel_corporate_logo_block';

    // @todo In function of language and logo source, prepare the necessary elements.
    NestedArray::setValue($build, ['#corporate_footer', 'legal_navigation'], $config->get('legal_navigation'));

    $this->setSiteSpecificFooter($build, $cache);

    $cache->applyTo($build);

    return $build;
  }

}
