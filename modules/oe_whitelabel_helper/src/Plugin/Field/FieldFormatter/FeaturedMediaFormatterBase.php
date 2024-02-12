<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for oe_featured_media formatters.
 */
abstract class FeaturedMediaFormatterBase extends ImageFormatter {

  use PluginDependencyTrait;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'display_caption' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['display_caption'] = [
      '#title' => $this->t('Display caption'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_caption'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $display_caption_setting = $this->getSetting('display_caption');
    if (!empty($display_caption_setting)) {
      $summary[] = t('Caption displayed');
    }

    return array_merge(parent::settingsSummary(), $summary);
  }

}
