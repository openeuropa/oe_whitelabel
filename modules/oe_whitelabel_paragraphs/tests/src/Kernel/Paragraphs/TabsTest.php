<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Component\Utility\Html;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the Tabs paragraph.
 */
class TabsTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_paragraphs_tabs',
    'twig_field_value',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['oe_paragraphs_tabs']);
    ConfigurableLanguage::createFromLangcode('el')->save();
  }

  /**
   * Tests the rendering of the tabs and their content, in every language.
   */
  public function testRendering(): void {
    $labels = [
      ['Overview', 'Επισκόπηση'],
      ['Main content', 'Κύριο περιεχόμενο'],
    ];
    $tabs = [];
    foreach ($labels as [$label, $greek]) {
      $tab = Paragraph::create([
        'type' => 'oe_tab',
        'field_oe_title' => $label,
        'field_oe_paragraphs' => [
          Paragraph::create([
            'type' => 'oe_rich_text',
            'field_oe_text_long' => ['value' => $label . ' text', 'format' => 'plain_text'],
          ]),
        ],
      ]);
      $tab->save();
      $tab->addTranslation('el', ['field_oe_title' => $greek])->save();
      $tabs[] = $tab;
    }
    $paragraph = Paragraph::create([
      'type' => 'oe_tabs',
      'field_oe_paragraphs' => $tabs,
    ]);
    $paragraph->save();
    $paragraph->addTranslation('el', $paragraph->toArray())->save();

    foreach (['en' => 0, 'el' => 1] as $langcode => $index) {
      Html::resetSeenIds();
      $crawler = new Crawler($this->renderParagraph($paragraph, $langcode));

      $links = $crawler->filter('.bcl-tabs nav.nav-tabs a.nav-link');
      $this->assertCount(2, $links);
      $panes = $crawler->filter('.bcl-tabs .tab-content > .tab-pane');
      $this->assertCount(2, $panes);
      foreach ($tabs as $delta => $tab) {
        $target = 'bcl-tab-' . $tab->id();
        $link = $links->eq($delta);
        $this->assertSame($labels[$delta][$index], trim($link->text()));
        $this->assertSame('#' . $target, $link->attr('href'));
        $this->assertSame($target . '-tab', $link->attr('id'));
        $this->assertSame('#' . $target, $link->attr('data-bs-target'));
        $this->assertSame($target, $link->attr('aria-controls'));

        $pane = $panes->eq($delta);
        $this->assertSame($target, $pane->attr('id'));
        $this->assertSame($target . '-tab', $pane->attr('aria-labelledby'));
        $this->assertSame($labels[$delta][0] . ' text', trim($pane->text()));
      }
      $this->assertStringContainsString('active', $links->eq(0)->attr('class'));
      $this->assertStringNotContainsString('active', $links->eq(1)->attr('class'));
      $this->assertStringContainsString('show active', $panes->eq(0)->attr('class'));
      $this->assertStringNotContainsString('active', $panes->eq(1)->attr('class'));
    }
  }

}
