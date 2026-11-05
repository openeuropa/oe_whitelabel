<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Tests the links to the tabs of the Tabs paragraph.
 *
 * @group batch1
 */
class TabsParagraphTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_paragraphs_tabs',
    'oe_whitelabel_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'oe_whitelabel';

  /**
   * The node with the Tabs paragraph.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The tab paragraphs.
   *
   * @var \Drupal\paragraphs\ParagraphInterface[]
   */
  protected array $tabs = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'paragraphs_test']);
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'oe_w_paragraphs',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => -1,
      'settings' => ['target_type' => 'paragraph'],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'paragraphs_test',
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'paragraphs_test')
      ->setComponent('oe_w_paragraphs', ['type' => 'entity_reference_revisions_entity_view'])
      ->save();
    // A format without filters, to keep the anchor id in the text.
    FilterFormat::create(['format' => 'full_html', 'name' => 'Full HTML'])->save();

    foreach (['First', 'Second', 'Third'] as $label) {
      $this->tabs[] = Paragraph::create([
        'type' => 'oe_tab',
        'field_oe_title' => $label,
        'field_oe_paragraphs' => [
          Paragraph::create([
            'type' => 'oe_rich_text',
            'field_oe_text_long' => [
              'value' => '<p id="' . strtolower($label) . '-anchor">' . $label . ' tab text</p>',
              'format' => 'full_html',
            ],
          ]),
        ],
      ]);
    }
    $this->node = $this->drupalCreateNode([
      'type' => 'paragraphs_test',
      'oe_w_paragraphs' => [
        Paragraph::create([
          'type' => 'oe_tabs',
          'field_oe_paragraphs' => $this->tabs,
        ]),
      ],
    ]);
  }

  /**
   * Tests that a URL hash opens its tab, and the URL follows the open tab.
   */
  public function testTabLinks(): void {
    $assert_session = $this->assertSession();

    // A link to a tab opens it.
    $this->drupalGet($this->node->toUrl('canonical', ['fragment' => 'bcl-tab-' . $this->tabs[1]->id()]));
    $this->assertActiveTab('Second');
    $assert_session->pageTextContains('Second tab text');
    $assert_session->pageTextNotContains('First tab text');

    // A link to an anchor inside a tab opens the tab.
    $this->drupalGet($this->node->toUrl('canonical', ['fragment' => 'third-anchor']));
    $this->assertActiveTab('Third');
    $assert_session->pageTextContains('Third tab text');

    // The URL follows the open tab.
    $this->getSession()->getPage()->clickLink('First');
    $this->assertActiveTab('First');
    $this->assertSame('#bcl-tab-' . $this->tabs[0]->id(), $this->getSession()->evaluateScript('return window.location.hash;'));

    // A hash change opens its tab too.
    $this->getSession()->executeScript('window.location.hash = "#bcl-tab-' . $this->tabs[2]->id() . '";');
    $this->assertActiveTab('Third');
  }

  /**
   * Asserts the open tab.
   *
   * @param string $label
   *   The label of the tab.
   */
  protected function assertActiveTab(string $label): void {
    $active = $this->assertSession()->waitForElementVisible('xpath', '//div[contains(@class, "bcl-tabs")]//a[contains(@class, "nav-link") and contains(@class, "active") and normalize-space() = "' . $label . '"]');
    $this->assertNotNull($active, sprintf('The "%s" tab is open.', $label));
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.bcl-tabs .nav-link.active'));
  }

}
