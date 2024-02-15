<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the configuration form of the teaser display plugin.
 */
class TeaserDisplayPluginFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'oe_whitelabel_link_lists',
    'oe_link_lists_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We use the claro theme for improved form support.
    \Drupal::service('theme_installer')->install(['claro']);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('admin', 'claro')
      ->save(TRUE);
  }

  /**
   * Tests the configuration form.
   */
  public function testPluginConfigurationForm(): void {
    $editor = $this->drupalCreateUser([
      'create dynamic link list',
      'edit dynamic link list',
      'view link list',
      'access link list canonical page',
      'access link list overview',
      'view the administration theme',
    ]);
    $this->drupalLogin($editor);
    $this->drupalGet('/link_list/add/dynamic');

    $page = $this->getSession()->getPage();
    $page->fillField('Administrative title', 'Teaser display plugin test');
    $page->selectFieldOption('Link source', 'Example source');
    $assert_session = $this->assertSession();
    $assert_session->assertWaitOnAjaxRequest();
    $page->selectFieldOption('Link display', 'Teaser');
    $assert_session->assertWaitOnAjaxRequest();
    $columns_field = $assert_session->fieldExists('Columns');
    $this->assertEquals(1, $columns_field->getValue());
    $columns_field->setValue('2');
    // The standard display fields are present.
    $assert_session->fieldExists('Number of items');
    $assert_session->selectExists('More link');
    $page->selectFieldOption('No results behaviour', 'Hide');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');
    $assert_session->statusMessageContains('Saved the Teaser display plugin test Link list.');

    $entities = \Drupal::entityTypeManager()
      ->getStorage('link_list')
      ->loadByProperties([
        'administrative_title' => 'Teaser display plugin test',
      ]);
    $this->assertNotEmpty($entities);
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = reset($entities);
    $configuration = $link_list->getConfiguration();
    $this->assertEquals([
      'plugin' => 'oewt_teaser',
      'plugin_configuration' => [
        'columns' => '2',
        'title' => NULL,
        'more' => [],
      ],
    ], $configuration['display']);

    $this->drupalGet($link_list->toUrl('edit-form'));
    // The columns max value is 3. Since we are running in a real browser,
    // the native browser validation will prevent the page from being
    // submitted.
    $columns_field->setValue('5');
    $page->pressButton('Save');
    $assert_session->statusMessageNotExists();
    $columns_field->setValue('4');
    $page->pressButton('Save');
    $assert_session->statusMessageNotExists();
    $columns_field->setValue('3');
    $page->pressButton('Save');
    $assert_session->statusMessageContains('Saved the Teaser display plugin test Link list.');
  }

}
