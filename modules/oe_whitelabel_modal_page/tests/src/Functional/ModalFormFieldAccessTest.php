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
    // Create a user with only basic modal administration permission.
    $user = $this->drupalCreateUser([
      'administer modal page',
      'access administration pages',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/modal/add');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    // Assert that basic fields are accessible.
    $assert_session->fieldExists('Title');
    $assert_session->fieldExists('Pages');

    // Assert that advanced fields are NOT accessible.
    $assert_session->fieldNotExists('Open this modal clicking on this element');
    $assert_session->fieldNotExists('Auto Open');
    $assert_session->fieldNotExists('Prevent Default');
    $assert_session->fieldNotExists('Class(es)', $assert_session->elementExists('xpath', '//details[./summary[.="MODAL HEADER"]]'));
    $assert_session->fieldNotExists('Class(es)', $assert_session->elementExists('xpath', '//details[./summary[.="MODAL FOOTER"]]'));
    $assert_session->elementNotExists('xpath', '//details[./summary[.="Cookies"]]');
    $assert_session->fieldNotExists('Label', $assert_session->elementExists('xpath', '//details[./summary[.="Button X close"]]'));
    $assert_session->fieldNotExists('ok_button_class');
    $assert_session->fieldNotExists('left_button_class');
    $assert_session->elementNotExists('xpath', '//details[./summary[.="Maximize Button"]]');
    $assert_session->elementNotExists('xpath', '//details[./summary[.="MODAL CLASS"]]');
    $assert_session->fieldNotExists('Parameters');
    $assert_session->fieldNotExists('Modal By');
  }

}
