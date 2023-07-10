<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\oe_link_lists\Entity\LinkList;
use Drupal\oe_whitelabel_link_lists\EntityValueExtractor;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists\EntityAwareLinkInterface;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_whitelabel_link_lists\ExternalUrlsInterface;
use Drupal\oe_whitelabel_link_lists\PatternEntityEnhancer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;


/**
 * View mode display of link list links.
 *
 * @LinkDisplay(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   description = @Translation("Allow to display a Link lists by chosing which Drupal display view mode."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class ViewModeDisplayPlugin extends ColumnLinkDisplayPluginBase implements ContainerFactoryPluginInterface {

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
     * The entity display repository.
     *
     * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
     */
    protected $entityDisplayRepository;


  /**
   * ViewModeDisplayPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Load the current LinkList entity from the form.
    /** @var LinkList $link_list */
    $link_list = $form_state->getFormObject()->getEntity();

    $config = $link_list->getConfiguration();

    // Create the Select form element.
    if ($link_list && $link_list instanceof \Drupal\oe_link_lists\Entity\LinkList &&
        !empty($config['source']['plugin_configuration']['entity_type'])
    ) {
      $plugin_configuration = $config['source']['plugin_configuration'];
      if ($plugin_configuration['bundle']) {
           // Load active displays as Select options.
          $options = $this->entityDisplayRepository->getViewModeOptionsByBundle($plugin_configuration['entity_type'], $plugin_configuration['bundle']);
          // Never render in full mode an entity.
          unset($options['default']);
          unset($options['full']);

          $form['view_mode'] = [
              '#type' => 'select',
              '#options' => $options,
              '#title' => $this->t('View mode'),
              '#description' => $this->t('Output the content in this view mode.'),
              '#default_value' => $this->configuration['view_mode'],
              '#access' => !empty($options),
              '#required' => TRUE,
          ];

        // @todo Solution 1: Add ajax to send "Source bundle" to plugin "View mode" when changing source.
        // @todo Solution 2: Multistep save entity in oe_link_lists module.
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');

    // Call the method from the trait to handle columns specific submission.
    if (method_exists($this, 'alterSubmitConfigurationForm')) {
      $this->alterSubmitConfigurationForm($form, $form_state);
    }
  }

  /**
   * @inheritDoc
   */
  protected function buildItems(LinkCollectionInterface $links): array {
    $items = [];

    /** @var \Drupal\oe_link_lists\LinkInterface $link */
    foreach ($links as $link) {
      $entity = $link instanceof EntityAwareLinkInterface ? $link->getEntity() : NULL;

      if (!$entity) {
        continue; // Skip invalid entities.
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Build the entity using the specified view mode.
      $view_display_id = $this->configuration['view_mode'];
      // Check if the entity type has the specified view mode available.
      $entity_type_id = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
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
