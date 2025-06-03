<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_search\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\oe_whitelabel_search\Plugin\Block\SearchBlock;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;

/**
 * Unit tests for SearchBlock validation logic.
 */
class SearchBlockValidateTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The oe_whitelabel_search block.
   *
   * @var \Drupal\oe_whitelabel_search\Plugin\Block\SearchBlock
   */
  protected $searchBlock;

  /**
   * {@inheritdoc}
   */
  public function setUp() : Void {
    parent::setUp();
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')->willReturn($this->storage);
    // Creates a simplified version of plugin definition.
    $plugin_definition = [
      'id' => 'whitelabel_search_block',
      'class' => SearchBlock::class,
      'provider' => 'oe_whitelabel_search',
    ];
    $this->searchBlock = $this->getMockBuilder(SearchBlock::class)
      ->setConstructorArgs([
        [],
        'whitelabel_search_block',
        $plugin_definition,
        $configFactory,
        $formBuilder,
        $this->moduleHandler,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['t'])
      ->getMock();
    // Mock t() to return the input string for predictable assertions.
    $this->searchBlock->method('t')->willReturnCallback(function ($string) {
      return $string;
    });
  }

  /**
   * Test: error if autocomplete enabled but modules missing.
   */
  public function testValidateAutocompleteModulesMissing() {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValues')->willReturn([
      'enable_autocomplete' => TRUE,
      'form_action' => '/search',
      'view_id' => 'any',
      'view_display' => 'any',
    ]);
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('enable_autocomplete', $this->stringContains('You must enable search_api_autocomplete'));
    $this->searchBlock->blockValidate([], $form_state);
  }

  /**
   * Test: error if form_action is absolute URL.
   */
  public function testValidateFormActionAbsoluteUrl() {
    $this->moduleHandler->method('moduleExists')->willReturn(TRUE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValues')->willReturn([
      'enable_autocomplete' => FALSE,
      'form_action' => 'http://example.com/search',
      'view_id' => '',
      'view_display' => '',
    ]);
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('form_action', $this->stringContains('The form action only supports relative path'));
    $this->searchBlock->blockValidate([], $form_state);
  }

  /**
   * Test: error if view does not exist when autocomplete enabled.
   */
  public function testValidateViewNotFound() {
    $this->moduleHandler->method('moduleExists')->willReturn(TRUE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValues')->willReturn([
      'enable_autocomplete' => TRUE,
      'form_action' => '/search',
      'view_id' => 'missing_view',
      'view_display' => 'any',
    ]);
    $this->storage->method('load')->willReturn(FALSE);
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('view_id', $this->stringContains('View id was not found.'));
    $this->searchBlock->blockValidate([], $form_state);
  }

  /**
   * Test: error if view display does not exist.
   */
  public function testValidateViewDisplayNotFound() {
    $this->moduleHandler->method('moduleExists')->willReturn(TRUE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValues')->willReturn([
      'enable_autocomplete' => TRUE,
      'form_action' => '/search',
      'view_id' => 'existing_view',
      'view_display' => 'missing_display',
    ]);
    $viewMock = $this->createMock(View::class);
    $viewMock->method('getDisplay')->willReturn(FALSE);
    $this->storage->method('load')->willReturn($viewMock);
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('view_display', $this->stringContains('View display was not found.'));
    $this->searchBlock->blockValidate([], $form_state);
  }

  /**
   * Test: valid config, no errors.
   */
  public function testValidateNoError() {
    $this->moduleHandler->method('moduleExists')->willReturn(TRUE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValues')->willReturn([
      'enable_autocomplete' => TRUE,
      'form_action' => '/search',
      'view_id' => 'existing_view',
      'view_display' => 'existing_display',
    ]);
    $viewMock = $this->createMock(View::class);
    $viewMock->method('getDisplay')->willReturn(TRUE);
    $this->storage->method('load')->willReturn($viewMock);
    $form_state->expects($this->never())
      ->method('setErrorByName');
    $this->searchBlock->blockValidate([], $form_state);
  }

}
