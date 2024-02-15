<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Base class for link display plugins that need to be displayed in columns.
 */
abstract class ColumnLinkDisplayPluginBase extends LinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'columns' => 1,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Columns'),
      '#min' => 1,
      '#max' => 3,
      '#default_value' => $this->configuration['columns'] ?? 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['columns'] = $form_state->getValue('columns');
  }

  /**
   * {@inheritdoc}
   */
  public function build(LinkCollectionInterface $links): array {
    $build = [];

    $items = $this->buildItems($links);
    if (empty($items)) {
      return $build;
    }

    // The content.
    $build['content'] = [
      '#type' => 'pattern',
      '#id' => 'section',
      '#heading' => $this->configuration['title'],
      '#content' => [
        '#type' => 'pattern',
        '#id' => 'columns',
        '#columns' => $this->configuration['columns'],
        '#items' => $items,
      ],
      '#attributes' => [
        'class' => [Html::getClass('link-list-display--' . $this->getPluginId())],
      ],
    ];

    return $build;
  }

  /**
   * Builds items.
   *
   * @param \Drupal\oe_link_lists\LinkCollectionInterface $links
   *   Links to be added in each column.
   *
   * @return array
   *   The renderable array.
   */
  abstract protected function buildItems(LinkCollectionInterface $links): array;

}
