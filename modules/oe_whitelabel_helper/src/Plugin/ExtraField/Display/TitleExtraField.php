<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\extra_field_plus\Plugin\ExtraFieldPlusDisplayBase;

/**
 * Title Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "extra_title",
 *   label = @Translation("Extra Field: Title"),
 *   bundles = {
 *     "node.*"
 *   },
 *   visible = false
 * )
 */
class TitleExtraField extends ExtraFieldPlusDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $settings = $this->getEntityExtraFieldSettings();

    // Prepare an empty render array.
    $render = [];

    // Convert attributes string to an array.
    $attributes = [];
    if (!empty($settings['attributes'])) {
      $attributes = $this->parseAttributes($settings['attributes']);
    }

    // Get the selected wrapper.
    $wrapper = $settings['wrapper'] ?? 'span';

    if ($settings['link_to_entity']) {
      // Create the URL for the entity.
      $url = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()], ['absolute' => TRUE]);

      // Create a Link object.
      $link = Link::fromTextAndUrl($entity->label(), $url);

      // Convert the Link object to a render array.
      $render = $link->toRenderable();

      // Assign the parsed attributes to the link's '#attributes' key.
      if (!empty($attributes)) {
        $render['#attributes'] = $attributes;
      }

      // Add the wrapper element.
      $render['#prefix'] = '<' . $wrapper . '>';
      $render['#suffix'] = '</' . $wrapper . '>';
    }
    else {
      // If not linking, just render the label with attributes and wrapper.
      $render = [
        '#type' => 'html_tag',
        '#tag' => $wrapper,
        '#value' => $entity->label(),
        '#attributes' => $attributes,
      ];
    }

    return $render;
  }

  /**
   * Helper function to parse attribute string into an array.
   *
   * @param string $attribute_string
   *   The attribute string (e.g., 'class="example" id="item"').
   *
   * @return array
   *   The attributes as an associative array.
   */
  protected function parseAttributes(string $attribute_string): array {
    $attributes = [];

    // Split the string into key-value pairs.
    preg_match_all('/(\w+)=["\']([^"\']+)["\']/', $attribute_string, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $attributes[$match[1]] = $match[2];
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  protected static function extraFieldSettingsForm(): array {
    $form = parent::extraFieldSettingsForm();

    $form['attributes'] = [
      '#type' => 'textfield',
      '#title' => t('Attributes'),
    ];

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => t('Link to the entity'),
    ];

    // Add a select element for the wrapper options.
    $form['wrapper'] = [
      '#type' => 'select',
      '#title' => t('Wrapper Element'),
      '#options' => [
        'span' => t('Span'),
        'div' => t('Div'),
        'h1' => t('H1'),
        'h2' => t('H2'),
        'h3' => t('H3'),
        'h4' => t('H4'),
        'h5' => t('H5'),
      ],
      '#default_value' => 'span',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected static function defaultExtraFieldSettings(): array {
    $values = parent::defaultExtraFieldSettings();

    $values += [
      'attributes' => '',
      'link_to_entity' => FALSE,
    // Default to span.
      'wrapper' => 'span',
    ];

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static function settingsSummary(string $field_id, string $entity_type_id, string $bundle, string $view_mode = 'default'): array {
    return [
      t('Attributes: @attributes', [
        '@attributes' => self::getExtraFieldSetting($field_id, 'attributes', $entity_type_id, $bundle, $view_mode),
      ]),
      t('Link to the entity: @link', [
        '@link' => self::getExtraFieldSetting($field_id, 'link_to_entity', $entity_type_id, $bundle, $view_mode) ? t('Yes') : t('No'),
      ]),
      t('Wrapper: @wrapper', [
        '@wrapper' => self::getExtraFieldSetting($field_id, 'wrapper', $entity_type_id, $bundle, $view_mode),
      ]),
    ];
  }

}
