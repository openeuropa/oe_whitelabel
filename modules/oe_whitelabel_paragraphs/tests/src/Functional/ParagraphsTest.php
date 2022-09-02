<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\CarouselPatternAssert;

/**
 * Tests paragraphs forms.
 */
class ParagraphsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'composite_reference',
    'oe_paragraphs_carousel',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createTestContentType();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Test Links Block paragraphs form.
   */
  public function testLinksBlockParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $this->getSession()->getPage()->pressButton('Add Links block');

    // Assert the Links Block fields appears.
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_links][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_links][0][title]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_text][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][oe_w_links_block_background]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][oe_w_links_block_orientation]');

    $this->submitForm([], 'Add another item');

    $values = [
      'title[0][value]' => 'Test Links block node title',
      'oe_w_paragraphs[0][subform][field_oe_text][0][value]' => 'EU Links',
      'oe_w_paragraphs[0][subform][field_oe_links][0][uri]' => 'https://www.example.com',
      'oe_w_paragraphs[0][subform][field_oe_links][0][title]' => 'Example link number 1',
      'oe_w_paragraphs[0][subform][field_oe_links][1][uri]' => 'https://www.more-example.com',
      'oe_w_paragraphs[0][subform][field_oe_links][1][title]' => 'Example link number 2',
      'oe_w_paragraphs[0][subform][oe_w_links_block_background]' => 'gray',
      'oe_w_paragraphs[0][subform][oe_w_links_block_orientation]' => 'vertical',
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are printed.
    $this->assertSession()->pageTextContains('EU Links');
    $this->assertSession()->pageTextContains('Example link number 1');
    $this->assertSession()->pageTextContains('Example link number 2');
  }

  /**
   * Test Social media follow paragraphs form.
   */
  public function testSocialMediaFollowParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $this->getSession()->getPage()->pressButton('Add Social media follow');

    // Assert the Social Media Follow fields appears.
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_links][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_links][0][title]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_links][0][link_type]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][oe_w_links_block_background]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_variant]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_see_more][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_social_media_see_more][0][title]');

    $this->submitForm([], 'Add another item');

    $values = [
      'title[0][value]' => 'Test Social Media follow Links node title',
      'oe_w_paragraphs[0][subform][field_oe_title][0][value]' => 'EU Social Media Follow Links',
      'oe_w_paragraphs[0][subform][field_oe_social_media_links][0][uri]' => 'https://www.facebook.com',
      'oe_w_paragraphs[0][subform][field_oe_social_media_links][0][title]' => 'Example Facebook',
      'oe_w_paragraphs[0][subform][field_oe_social_media_links][0][link_type]' => 'facebook',
      'oe_w_paragraphs[0][subform][oe_w_links_block_background]' => 'transparent',
      'oe_w_paragraphs[0][subform][field_oe_social_media_variant]' => 'horizontal',
      'oe_w_paragraphs[0][subform][field_oe_social_media_see_more][0][uri]' => 'https://example.com',
      'oe_w_paragraphs[0][subform][field_oe_social_media_see_more][0][title]' => 'More channels',
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are printed.
    $this->assertSession()->pageTextContains('EU Social Media Follow Links');
    $this->assertSession()->pageTextContains('Example Facebook');
    $this->assertSession()->pageTextContains('More channels');
  }

  /**
   * Test Accordion paragraphs form.
   */
  public function testAccordionParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $page = $this->getSession()->getPage();
    $page->pressButton('Add Accordion');

    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]');
    // Assert the Icon field is not shown.
    $this->assertSession()->fieldNotExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_icon][0][value]');

    $values = [
      'title[0][value]' => 'Test Accordion',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text][0][value]' => 'Title item 1',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are displayed correctly.
    $this->assertSession()->pageTextContains('Title item 1');
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
  }

  /**
   * Test Facts and figures paragraphs form.
   */
  public function testFactsFiguresParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $page = $this->getSession()->getPage();
    $page->pressButton('Add Facts and figures');
    // Assert the Facts and figures fields are present.
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_link][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_link][0][title]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][oe_w_n_columns][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_subtitle][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_plain_text_long][0][value]');

    $values = [
      'title[0][value]' => 'Test Fact and figures node title',
      'oe_w_paragraphs[0][subform][field_oe_title][0][value]' => 'Fact and figures block',
      'oe_w_paragraphs[0][subform][field_oe_link][0][uri]' => 'https://www.google.com',
      'oe_w_paragraphs[0][subform][field_oe_link][0][title]' => 'Read more',
      'oe_w_paragraphs[0][subform][oe_w_n_columns][0][value]' => 2,
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]' => "1529 JIRA Ticket",
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_subtitle][0][value]' => "Jira Tickets",
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_plain_text_long][0][value]' => "Nunc condimentum sapien ut nibh finibus suscipit vitae at justo. Morbi quis odio faucibus, commodo tortor id, elementum libero.",
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are displayed correctly.
    $this->assertSession()->pageTextContains('Fact and figures block');
    $this->assertSession()->pageTextContains('Read more');
    $this->assertSession()->pageTextContains('1529 JIRA Ticket');
    $this->assertSession()->pageTextContains('Jira Tickets');
    $this->assertSession()->pageTextContains('Nunc condimentum sapien ut nibh finibus suscipit vitae at justo. Morbi quis odio faucibus, commodo tortor id, elementum libero.');
  }

  /**
   * Test icon options event subscriber.
   */
  public function testIconOptionsEventsubscriber(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $page = $this->getSession()->getPage();
    $page->pressButton('Add Fact');

    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_icon]');
    $allowed_values = [
      '_none',
      'arrow-down',
      'box-arrow-up',
      'arrow-up',
      'book',
      'camera',
      'check',
      'download',
      'currency-euro',
      'facebook',
      'file',
      'image',
      'info',
      'linkedin',
      'files',
      'rss',
      'search',
      'share',
      'twitter',
      'camera-video',
    ];
    foreach ($allowed_values as $allowed_value) {
      $this->assertSession()->elementsCount('css', 'option[value="' . $allowed_value . '"]', 1);
    }
    $this->assertSession()->elementsCount('css', 'select#edit-oe-w-paragraphs-0-subform-field-oe-icon option', 20);

  }

  /**
   * Test Description list paragraphs form.
   */
  public function testDescriptionListParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $this->getSession()->getPage()->pressButton('Add Description list');

    $assert_session = $this->assertSession();
    $assert_session->fieldExists('oe_w_paragraphs[0][subform][field_oe_title][0][value]');
    $this->assertEquals([
      'horizontal' => 'Horizontal',
      'vertical' => 'Vertical',
    ], $this->getOptions('oe_w_paragraphs[0][subform][oe_w_orientation]'));
    $assert_session->fieldExists('oe_w_paragraphs[0][subform][field_oe_description_list_items][0][term]');
    $assert_session->fieldExists('oe_w_paragraphs[0][subform][field_oe_description_list_items][0][description][value]');

    $values = [
      'title[0][value]' => 'Test Description list node title',
      'oe_w_paragraphs[0][subform][field_oe_title][0][value]' => 'Description list paragraph',
      'oe_w_paragraphs[0][subform][oe_w_orientation]' => 'horizontal',
      'oe_w_paragraphs[0][subform][field_oe_description_list_items][0][term]' => 'Aliquam ultricies',
      'oe_w_paragraphs[0][subform][field_oe_description_list_items][0][description][value]' => 'Donec et leo ac velit posuere tempor mattis ac mi. Vivamus nec dictum lectus. Aliquam ultricies placerat eros, vitae ornare sem.',
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are displayed correctly.
    $assert_session->pageTextContains('Description list paragraph');
    $assert_session->pageTextContains('Aliquam ultricies');
    $assert_session->pageTextContains('Donec et leo ac velit posuere tempor mattis ac mi. Vivamus nec dictum lectus. Aliquam ultricies placerat eros, vitae ornare sem.');
  }

  /**
   * Test Links Block paragraphs form.
   */
  public function testListingParagraph(): void {
    $this->drupalGet('/node/add/paragraphs_test');
    $page = $this->getSession()->getPage();
    $page->pressButton('Add Listing item block');

    // Assert the Listing fields appears.
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][variant]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_list_item_block_layout]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_link][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_meta][0][value]');
    $this->assertSession()->fieldExists('files[oe_w_paragraphs_0_subform_field_oe_paragraphs_0_subform_field_oe_image_0]');

    $this->submitForm([], 'Add another item');

    $values = [
      'title[0][value]' => 'Listing node title',
      'oe_w_paragraphs[0][variant]' => 'default',
      'oe_w_paragraphs[0][subform][field_oe_title][0][value]' => 'Listing example',
      'oe_w_paragraphs[0][subform][field_oe_list_item_block_layout]' => 'two_columns',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_link][0][uri]'  => 'https://www.example.com',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_title][0][value]'  => 'Card title',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_text_long][0][value]'  => 'Lorem Ipsum dolor sit amet.',
      'oe_w_paragraphs[0][subform][field_oe_paragraphs][0][subform][field_oe_meta][0][value]'  => 'label1',
    ];

    $this->submitForm($values, 'Save');
    $this->drupalGet('/node/1');

    // Assert paragraph values are displayed correctly.
    $this->assertSession()->pageTextContains('Listing example');
    $this->assertSession()->pageTextContains('Card title');
    $this->assertSession()->pageTextContains('Lorem Ipsum dolor sit amet.');
    $this->assertSession()->pageTextContains('label1');
  }

  /**
   * Test Carousel paragraphs form.
   */
  public function testCarouselParagraph(): void {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $fixtures_path = \Drupal::service('extension.list.module')->getPath('oe_whitelabel_paragraphs') . '/tests/fixtures/';

    $file_1 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1.jpeg');
    $file_1->setPermanent();
    $file_1->save();

    $file_2 = file_save_data(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_2.jpeg');
    $file_2->setPermanent();
    $file_2->save();

    $media_storage = \Drupal::service('entity_type.manager')->getStorage('media');

    $media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'First image',
      'oe_media_image' => [
        'target_id' => $file_1->id(),
      ],
    ]);
    $media->save();
    $media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'Second image',
      'oe_media_image' => [
        'target_id' => $file_2->id(),
      ],
    ]);
    $media->save();

    $this->drupalGet('/node/add/paragraphs_test');
    $page->pressButton('Add Carousel');

    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_title][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_text][0][value]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_link][0][uri]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_link][0][title]');
    $this->assertSession()->fieldExists('oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_media][0][target_id]');

    $values = [
      'title[0][value]' => 'Title',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_title][0][value]' => 'Carousel item 1',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_text][0][value]' => 'Caption 1',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_link][0][uri]'  => 'https://www.example1.com',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_link][0][title]'  => 'Link 1',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][0][subform][field_oe_media][0][target_id]'  => 'First image',
    ];

    $this->submitForm($values, 'Save');

    $this->assertSession()->pageTextContains('The Carousel paragraph should contain at least 2 items.');

    $this->submitForm([], 'Add Carousel item');
    $values += [
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][1][subform][field_oe_title][0][value]' => 'Carousel item 2',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][1][subform][field_oe_text][0][value]' => 'Caption 2',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][1][subform][field_oe_link][0][uri]'  => 'https://www.example2.com',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][1][subform][field_oe_link][0][title]'  => 'Link 2',
      'oe_w_paragraphs[0][subform][field_oe_carousel_items][1][subform][field_oe_media][0][target_id]'  => 'Second image',
    ];
    $this->submitForm($values, 'Save');

    // Assert paragraph values are displayed correctly.
    $assert->pageTextContains('Title');
    $paragraph = $assert->elementExists('css', 'div.carousel');

    // Assert paragraph rendering for English version.
    $assert = new CarouselPatternAssert();
    $expected_values = [
      'items' => [
        [
          'caption_title' => 'Carousel item 1',
          'caption' => 'Caption 1',
          'link' => [
            'label' => 'Link 1',
            'path' => 'https://www.example1.com',
          ],
          'image' => [
            'src' => file_create_url($file_1->getFileUri()),
            'alt' => 'First image',
          ],
        ],
        [
          'caption_title' => 'Carousel item 2',
          'caption' => 'Caption 2',
          'link' => [
            'label' => 'Link 2',
            'path' => 'https://www.example2.com',
          ],
          'image' => [
            'src' => file_create_url($file_2->getFileUri()),
            'alt' => 'Second image',
          ],
        ],
      ],
    ];
    $assert->assertPattern($expected_values, $paragraph->getOuterHtml());
  }

  /**
   * Creates a node type with a paragraphs field.
   */
  protected function createTestContentType() {
    $this->drupalCreateContentType([
      'type' => 'paragraphs_test',
      'name' => 'Paragraphs Test',
    ]);

    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'oe_w_paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'paragraphs_test',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ])->save();

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'paragraphs_test');
    $form_display = $form_display->setComponent('oe_w_paragraphs', ['type' => 'oe_paragraphs_variants']);
    $form_display->save();

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'paragraphs_test');
    $view_display->setComponent('oe_w_paragraphs', ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

}
