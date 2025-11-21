<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_modal_page\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\modal_page\Form\ModalForm as OriginalModalForm;

/**
 * Override of modal configuration form.
 *
 * @see \Drupal\modal_page\Form\ModalForm
 */
class ModalForm extends OriginalModalForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Check if user has permission for advanced modal page configuration.
    if (!$this->currentUser()->hasPermission('administer advanced modal page configuration')) {
      $form['open_modal_on_element_click']['#access'] = FALSE;
      $form['auto_open']['#access'] = FALSE;
      $form['prevent_default']['#access'] = FALSE;
      $form['modal_header']['enable']['header_class_details']['#access'] = FALSE;
      $form['modal_footer']['enable']['footer_class_details']['#access'] = FALSE;
      $form['modal_footer']['enable']['dont_show_again']['close_modal_cookie']['#access'] = FALSE;
      $form['modal_buttons']['button_close']['top_right_button_class']['#access'] = FALSE;
      $form['modal_buttons']['button_close']['top_right_button_label']['#access'] = FALSE;
      $form['modal_buttons']['ok_button']['ok_button_class']['#access'] = FALSE;
      $form['modal_buttons']['left_button']['left_button_class']['#access'] = FALSE;
      $form['modal_buttons']['maximize_button']['#access'] = FALSE;
      $form['modal_class']['#access'] = FALSE;
      $form['parameters']['#access'] = FALSE;
      $form['extras']['type']['#access'] = FALSE;
    }

    return $form;
  }

}
