<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_modal_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests modal form field access based on permissions.
 */
class ModalFormFieldAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_whitelabel_modal_page',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that advanced fields are hidden without permission.
   */
  public function testAdvancedFieldsHiddenWithoutPermission(): void {
    $this->loginWithAdvancedPermission(FALSE);
    $this->assertAdvancedFieldsVisibility(FALSE);
  }

  /**
   * Tests that advanced fields are visible with permission.
   */
  public function testAdvancedFieldsVisibleWithPermission(): void {
    $this->loginWithAdvancedPermission(TRUE);
    $this->assertAdvancedFieldsVisibility(TRUE);
  }

  /**
   * Logs in a modal user with or without the advanced permission.
   */
  protected function loginWithAdvancedPermission(bool $has_permission): void {
    $permissions = [
      'administer modal page',
      'access administration pages',
    ];

    if ($has_permission) {
      $permissions[] = 'administer advanced modal page configuration';
    }

    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);
  }

  /**
   * Asserts visibility of advanced fields based on the given flag.
   */
  protected function assertAdvancedFieldsVisibility(bool $should_exist): void {
    $this->drupalGet('/admin/structure/modal/add');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    // Assert that basic fields are accessible.
    $assert_session->fieldExists('Title');
    $assert_session->fieldExists('Pages');

    $field_assert = $should_exist ? 'fieldExists' : 'fieldNotExists';
    $element_assert = $should_exist ? 'elementExists' : 'elementNotExists';

    // Assert visibility of advanced fields.
    $assert_session->{$field_assert}('Open this modal clicking on this element');
    $assert_session->{$field_assert}('Auto Open');
    $assert_session->{$field_assert}('Prevent Default');
    $assert_session->{$field_assert}('Class(es)', $assert_session->elementExists('xpath', '//details[./summary[.="MODAL HEADER"]]'));
    $assert_session->{$field_assert}('Class(es)', $assert_session->elementExists('xpath', '//details[./summary[.="MODAL FOOTER"]]'));
    $assert_session->{$element_assert}('xpath', '//details[./summary[.="Cookies"]]');
    $assert_session->{$field_assert}('Label', $assert_session->elementExists('xpath', '//details[./summary[.="Button X close"]]'));
    $assert_session->{$field_assert}('ok_button_class');
    $assert_session->{$field_assert}('left_button_class');
    $assert_session->{$element_assert}('xpath', '//details[./summary[.="Maximize Button"]]');
    $assert_session->{$element_assert}('xpath', '//details[./summary[.="MODAL CLASS"]]');
    $assert_session->{$field_assert}('Parameters');
    $assert_session->{$field_assert}('Modal By');
  }

}
