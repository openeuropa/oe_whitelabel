<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\oe_link_lists\EntityAwareLinkInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Base class for link display plugins that need to be displayed in columns.
 */
abstract class ColumnLinkDisplayPluginBase extends LinkDisplayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ColumnLinkDisplayPluginBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'columns' => 1,
      'equal_height' => TRUE,
      'background_color' => 'bg-white',
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

    $form['equal_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equal height'),
      '#default_value' => $this->configuration['equal_height'] ?? TRUE,
    ];

    $form['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background color'),
      // Backgroud HTML classes used in oe_whitelabel.
      '#options' => [
        'bg-white' => $this->t('White'),
        'bg-light' => $this->t('Light'),
      ],
      '#default_value' => $this->configuration['background_color'] ?? 'bg-white',
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
      if (!empty($this->configuration['equal_height'])) {
        // Parent wrapper.
        $attributes->addClass('h-100');
        // Child have to take the height of the parent.
        if (!empty($item['content'])) {
          $content_attributes = new Attribute($item['content']['attributes'] ?? []);
          $content_attributes->addClass('h-100');
        }
      }
      // Background color.
      if (!empty($this->configuration['background_color'])) {
        $content_attributes->addClass($this->configuration['background_color']);
      }

      // Set values.
      $item['#attributes'] = $attributes->toArray();
      if (!empty($item['content']) && !empty($content_attributes)) {
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

  /**
   * Builds items.
   *
   * @param \Drupal\oe_link_lists\LinkCollectionInterface $links
   *   Links to be added in each column.
   *
   * @return array
   *   The renderable array (using Pattern).
   */
  protected function buildItems(LinkCollectionInterface $links): array {
    $items = [];

    /** @var \Drupal\oe_link_lists\LinkInterface $link */
    foreach ($links as $link) {
      $entity = $link instanceof EntityAwareLinkInterface ? $link->getEntity() : NULL;

      if (!$entity) {
        // Skip invalid entities.
        continue;
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Check if the entity type has the view display available.
      $entity_type_id = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $view_display_id = $this->pluginId;
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $view_display = $storage->load($entity_type_id . '.' . $bundle . '.' . $view_display_id);

      if (!$view_display) {
        $sub_path = $entity_type_id == 'node' ? 'types' : $entity->getEntityTypeId();
        $link_here = Link::fromTextAndUrl($this->t('here'),
          Url::fromUri('internal:' . '/admin/structure/' . $sub_path . '/manage/' . $bundle . '/display#edit-modes')
        )->toString();
        $message = new FormattableMarkup('The <b>@view_display</b> view mode is not available for <b>@bundle</b>. Please enable it @link_here', [
          '@view_display' => $view_display_id,
          '@bundle' => $entity->bundle(),
          '@link_here' => $link_here,
        ]);
        \Drupal::messenger()->addError($message);
        return $items;
      }
      $renderable_array = $this->entityTypeManager->getViewBuilder($entity_type_id)->view($entity, $view_display_id);

      $items[] = ['content' => $renderable_array];
    }
    return $items;
  }

}
