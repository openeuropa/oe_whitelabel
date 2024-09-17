<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_user_profile\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a Block with a link to edit the user profile.
 *
 * @Block(
 *   id = "user_profile_cta",
 *   admin_label = @Translation("User Manage Profile"),
 *   category = @Translation("OpenEuropa Whitelabel"),
 * )
 */
class UserProfileCta extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $uid = \Drupal::routeMatch()->getRawParameter('user');

    $title = $this->t('Manage Profile');
    if (!empty($config['link_title'])) {
      $title = $config['link_title'];
    }

    $url = Url::fromUserInput("/user/{$uid}/edit");
    $link_options = [
      'attributes' => [
        'class' => [
          'text-decoration-none',
          'text-reset',
        ],
      ],
    ];
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl($title, $url);

    return [
      '#markup' => $link->toString(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Title for the User Profile link'),
      '#default_value' => $config['link_title'] ?? $this->t('Manage Profile'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['link_title'] = $values['link_title'];
  }

}
