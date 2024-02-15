<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\oe_bootstrap_theme\PatternAssertion\FilePatternAssert;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the document paragraph.
 */
class DocumentParagraphTest extends ParagraphsTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'file_link_test',
    'language',
    'node',
    'oe_paragraphs_document',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The node dependency is wrongfully forced by oe_media_media_access().
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installConfig([
      'content_translation',
      'language',
      'media',
      'oe_media',
    ]);

    $this->container->get('module_handler')->loadInclude('oe_paragraphs_media_field_storage', 'install');
    oe_paragraphs_media_field_storage_install(FALSE);
    $this->installConfig(['oe_paragraphs_document']);

    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Enable translations for the document media bundle.
    $this->container->get('content_translation.manager')->setEnabled('media', 'document', TRUE);
    // Make fields translatable.
    $field_ids = [
      'media.document.oe_media_file_type',
      'media.document.oe_media_remote_file',
      'media.document.oe_media_file',
    ];
    foreach ($field_ids as $field_id) {
      $field_config = $this->container->get('entity_type.manager')->getStorage('field_config')->load($field_id);
      $field_config->set('translatable', TRUE)->save();
    }
    $this->container->get('router.builder')->rebuild();

    // Simulate the presence of test remote files. This avoids real requests to
    // external websites.
    $settings = Settings::getAll();
    $settings['file_link_test_middleware'] = [
      'http://oe_whitelabel.drupal/spanish-document.txt' => [
        'status' => 200,
        'headers' => [
          'Content-Type' => 'text/plain',
          'Content-Length' => 45187,
        ],
      ],
      'http://oe_whitelabel.drupal/spreadsheet.xls' => [
        'status' => 200,
        'headers' => [
          'Content-Type' => 'application/vnd.ms-excel',
          'Content-Length' => 78459784,
        ],
      ],
    ];
    new Settings($settings);

    // Tests need to run with user 1 as access checks prevent entity reference
    // rendering otherwise.
    $this->setCurrentUser(User::load(1));
  }

  /**
   * Tests the file paragraph rendering.
   */
  public function testRendering(): void {
    $uri_en = $this->container->get('file_system')->copy(
      $this->container->get('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://test.pdf'
    );
    $pdf_en = File::create(['uri' => $uri_en]);
    $pdf_en->save();

    $local_media = Media::create([
      'bundle' => 'document',
      'name' => 'Local PDF file',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        'target_id' => $pdf_en->id(),
      ],
    ]);
    $local_media->save();

    $paragraph = Paragraph::create([
      'type' => 'oe_document',
      'field_oe_media' => [
        'target_id' => $local_media->id(),
      ],
    ]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $paragraph_wrapper = $crawler->filter('.paragraph');
    $this->assertCount(1, $paragraph_wrapper);

    $expected = [
      'file' => [
        'title' => 'Local PDF file',
        'language' => 'English',
        'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri_en),
        'meta' => '(2.96 KB - PDF)',
        'icon' => 'file-pdf-fill',
      ],
      'translations' => NULL,
      'link_label' => 'Download',
    ];
    $assert = new FilePatternAssert();
    $assert->assertPattern($expected, $paragraph_wrapper->html());

    // Add an Italian translation for the media.
    $uri_it = $this->container->get('file_system')->copy(
      $this->container->get('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://test_it.pdf'
    );
    $pdf_it = File::create(['uri' => $uri_it]);
    $pdf_it->save();
    $local_media->addTranslation('it', [
      'name' => 'Italian translation',
      'oe_media_file_type' => 'local',
      'oe_media_file' => [
        'target_id' => $pdf_it->id(),
      ],
    ]);
    $local_media->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $paragraph_wrapper = $crawler->filter('.paragraph');
    $this->assertCount(1, $paragraph_wrapper);
    $expected['translations'] = [
      [
        'title' => 'Italian translation',
        'language' => 'Italian',
        'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri_it),
        'meta' => '(2.96 KB - PDF)',
      ],
    ];
    $assert->assertPattern($expected, $paragraph_wrapper->html());

    // Add a Spanish translation that points to a remote file.
    $local_media->addTranslation('es', [
      'name' => 'Spanish translation',
      'oe_media_file_type' => 'remote',
      'oe_media_remote_file' => 'http://oe_whitelabel.drupal/spanish-document.txt',
    ]);
    $local_media->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $paragraph_wrapper = $crawler->filter('.paragraph');
    $this->assertCount(1, $paragraph_wrapper);
    $expected['translations'][] = [
      'title' => 'Spanish translation',
      'language' => 'Spanish',
      'url' => 'http://oe_whitelabel.drupal/spanish-document.txt',
      'meta' => '(44.13 KB - TXT)',
    ];
    $assert->assertPattern($expected, $paragraph_wrapper->html());

    // Test a remote document as main file, to make sure that the
    // DocumentMediaWrapper class is tested in all scenarios.
    $remote_media = Media::create([
      'bundle' => 'document',
      'name' => 'Remote XLS file',
      'oe_media_file_type' => 'remote',
      'oe_media_remote_file' => 'http://oe_whitelabel.drupal/spreadsheet.xls',
    ]);
    $remote_media->save();

    $paragraph = Paragraph::create([
      'type' => 'oe_document',
      'field_oe_media' => [
        'target_id' => $remote_media->id(),
      ],
    ]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $paragraph_wrapper = $crawler->filter('.paragraph');
    $this->assertCount(1, $paragraph_wrapper);

    $expected = [
      'file' => [
        'title' => 'Remote XLS file',
        'language' => 'English',
        'url' => 'http://oe_whitelabel.drupal/spreadsheet.xls',
        'meta' => '(74.83 MB - XLS)',
        'icon' => 'file-excel-fill',
      ],
      'translations' => NULL,
      'link_label' => 'Download',
    ];
    $assert = new FilePatternAssert();
    $assert->assertPattern($expected, $paragraph_wrapper->html());
  }

}
