<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
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

    $form['equal_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equal height'),
      '#default_value' => $this->configuration['equal_height'] ?? TRUE,
    ];

    $form['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background color'),
      '#options' => [
        'bg-white' => $this->t('White'),
        'bg-light' => $this->t('Light'),
      ],
      '#default_value' => $this->configuration['background_color'] ?? 'white',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['columns'] = $form_state->getValue('columns');
    $this->configuration['equal_height'] = $form_state->getValue('equal_height');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build(LinkCollectionInterface $links): array {
    $build = parent::build($links);

    $items = $this->buildItems($links);
    if (empty($items)) {
      return $build;
    }

    // Set additional attributes.
    foreach ($items as &$item) {
      $attributes = new Attribute($item['attributes'] ?? []);
      // Equal height.
      if(!empty($this->configuration['equal_height'])) {
        // Parent wrapper.
        $attributes->addClass('h-100');
        // Child have to take the height of the parent.
        if(!empty($item['content'])) {
          $content_attributes = new Attribute($item['content']['attributes'] ?? []);
          $content_attributes->addClass('h-100');
        }
      }
      // Background color.
      if(!empty($this->configuration['background_color'])) {
        $content_attributes->addClass($this->configuration['background_color']);
      }

      // Set values.
      $item['#attributes'] = $attributes->toArray();
      if(!empty($item['content']) && !empty($content_attributes)) {
        $item['content']['#attributes'] = $content_attributes->toArray();
      }
    }


    // The content.
    $build['content'] = [
      '#type' => 'pattern',
      '#id' => 'listing',
      '#fields' => [
        'columns' => $this->configuration['columns'],
        'title' => $this->configuration['title'],
        'items' => $items,
        'attributes' => $attributes,
      ],
    ];

    return $build;
  }

}
