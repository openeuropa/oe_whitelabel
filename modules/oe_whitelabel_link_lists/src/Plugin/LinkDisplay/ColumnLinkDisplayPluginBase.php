<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Base class for link display plugins that need to be displayed in columns.
 */
abstract class ColumnLinkDisplayPluginBase extends LinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Builds items.
   *
   * @param \Drupal\oe_link_lists\LinkCollectionInterface $links
   *   Links to be added in each column.
   *
   * @return array
   *   The renderable array (using Pattern).
   */
  abstract protected function buildItems(LinkCollectionInterface $links): array;

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
    $id = Html::getUniqueId('columns');
    $form['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Columns'),
      '#min' => 1,
      '#max' => 3,
      '#default_value' => $this->configuration['columns'] ?? 1,
      '#id' => $id,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
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
    $build[] = [
      '#type' => 'pattern',
      '#id' => 'listing',
      '#fields' => [
        'columns' => $this->configuration['columns'],
        'title' => $this->configuration['title'],
        'items' => $items,
      ],
    ];
    // The more link.
    $more_link = $this->configuration['more'];
    if ($more_link instanceof Link) {
      $build['more'] = $more_link->toRenderable();
      $build['more']['#access'] = $more_link->getUrl()->access();
    }
    return $build;
  }

}
