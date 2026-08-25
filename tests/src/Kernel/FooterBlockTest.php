<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;
use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the EU and the EC corporate Footer blocks rendering.
 */
class FooterBlockTest extends SparqlKernelTestBase {

  use SkosImportTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_bootstrap_theme_helper',
    'oe_corporate_blocks',
    'oe_corporate_site_info',
    'oe_whitelabel_helper',
    'rdf_skos',
    'system',
    'ui_patterns',
    'ui_patterns_library',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'oe_corporate_site_info',
      'oe_corporate_blocks',
    ]);

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('name', 'Footer block test website')
      ->save();

    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->import($base_url, $this->sparql, 'phpunit');
    $this->enableGraph('fruit');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->clear($base_url, $this->sparql, 'phpunit');

    parent::tearDown();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testEcFooterBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->load('oe_whitelabel_ec_corporate_footer');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $crawler = new Crawler((string) $this->container->get('renderer')->renderRoot($build));

    // For now we assert only minimal till we have a footer component.
    $this->assertCount(1, $crawler->filter('footer.bcl-footer--ec'));
    $rows = $crawler->filter('.row');
    $this->assertCount(2, $rows);
    $section_titles = $crawler->filter('p.fw-bold.mb-2');
    $this->assertCount(2, $section_titles);
    $this->assertEquals('Footer block test website', $section_titles->eq(0)->text());
    $this->assertEquals('European Commission', $section_titles->eq(1)->text());
    $bordered_sections = $crawler->filter('.bcl-footer__bordered-row');
    $this->assertCount(1, $bordered_sections);
    $columns = $bordered_sections->filter('div.col-12.col-lg-4');
    $this->assertCount(3, $columns);
    // The first column doesn't contain any link.
    $this->assertEmpty($columns->eq(0)->filter('a'));
    $this->assertEquals('European Commission', trim($columns->eq(0)->text()));
    // The number of links in the footer can vary based on the release of
    // oe_corporate_blocks, so we cannot assert a specific count.
    $this->assertNotEmpty($columns->eq(1)->filter('.mb-1 a.standalone'));
    $this->assertNotEmpty($columns->eq(2)->filter('.mb-1 a.standalone'));
    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(0, $accessibility_link);

    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('accessibility', 'https://example.com/accessibility')
      ->save();

    $builder->resetCache([$entity]);
    $build = $builder->view($entity, 'block');
    $crawler = new Crawler((string) $this->container->get('renderer')->renderRoot($build));

    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(1, $accessibility_link);
    $this->assertEquals('Accessibility', $accessibility_link->text());
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testEuFooterBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'eufooterblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_corporate_blocks_eu_footer',
      'settings' => [
        'id' => 'oe_corporate_blocks_eu_footer',
        'label' => 'EU Footer block',
        'provider' => 'oe_corporate_blocks',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // For now we assert only minimal till we have a footer component.
    $this->assertCount(1, $crawler->filter('footer.bcl-footer--eu'));
    $rows = $crawler->filter('.row');
    $this->assertCount(2, $rows);
    $borderedSections = $crawler->filter('.bcl-footer__bordered-row');
    $this->assertCount(1, $borderedSections);
    $sectionTitles = $crawler->filter('p.fw-bold.mb-2');
    $this->assertCount(5, $sectionTitles);
    $sectionLinks = $crawler->filter('div.col-12.col-lg-4:nth-child(2) .mb-1 a.standalone');
    $this->assertCount(5, $sectionLinks);
    $accessibility_eu_link = $crawler->filter('a[href="https://european-union.europa.eu/accessibility-statement_en"]');
    $this->assertCount(1, $accessibility_eu_link);
    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(0, $accessibility_link);

    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('accessibility', 'https://example.com/accessibility')
      ->save();

    $builder->resetCache([$entity]);
    $build = $builder->view($entity, 'block');
    $crawler = new Crawler((string) $this->container->get('renderer')->renderRoot($build));

    $accessibility_eu_link = $crawler->filter('a[href="https://european-union.europa.eu/accessibility-statement_en"]');
    $this->assertCount(0, $accessibility_eu_link);
    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(1, $accessibility_link);
    $this->assertEquals('Accessibility statement', $accessibility_link->text());
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testNeutralFooterBlockRendering(): void {
    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('accessibility', 'https://example.com/accessibility')
      ->save();

    $crawler = $this->renderNeutralFooterBlock();

    // For now we assert only minimal till we have a footer component.
    $this->assertCount(1, $crawler->filter('footer.bcl-footer--neutral'));
    $rows = $crawler->filter('.row');
    $this->assertCount(1, $rows);
    $sectionTitles = $crawler->filter('p.fw-bold.mb-2');
    $this->assertCount(2, $sectionTitles);
    $accessibilityLink = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(1, $accessibilityLink);
    $this->assertEquals('Accessibility', $accessibilityLink->text());
  }

  /**
   * Tests the neutral footer with a single site owner.
   */
  public function testNeutralFooterSingleSiteOwner(): void {
    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('site_owners', ['http://example.com/fruit/apple'])
      ->save();

    $crawler = $this->renderNeutralFooterBlock();
    $footer_text = trim($crawler->filter('footer.bcl-footer--neutral')->text());

    $this->assertStringContainsString('This site is managed by the Apple', $footer_text);
  }

  /**
   * Tests the neutral footer without site owners.
   */
  public function testNeutralFooterWithoutSiteOwner(): void {
    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('site_owners', [])
      ->save();

    $crawler = $this->renderNeutralFooterBlock();
    $footer_text = trim($crawler->filter('footer.bcl-footer--neutral')->text());

    $this->assertStringNotContainsString('This site is managed by the', $footer_text);
  }

  /**
   * Tests the neutral footer with multiple site owners.
   */
  public function testNeutralFooterMultipleSiteOwners(): void {
    \Drupal::configFactory()
      ->getEditable('oe_corporate_site_info.settings')
      ->set('site_owners', [
        'http://example.com/fruit/apple',
        'http://example.com/fruit/pear',
        'http://example.com/fruit/citrus-fruit',
      ])
      ->save();

    $crawler = $this->renderNeutralFooterBlock();
    $footer_html = trim($crawler->filter('footer.bcl-footer--neutral')->html());

    $this->assertStringContainsString('<p>This site is co-managed by:<br>Apple<br>Pear<br>Citrus fruit</p>', $footer_html);
  }

  /**
   * Renders the neutral footer block.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   A crawler for the rendered block output.
   */
  protected function renderNeutralFooterBlock(): Crawler {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'neutralfooterblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_corporate_blocks_neutral_footer',
      'settings' => [
        'id' => 'oe_corporate_blocks_neutral_footer',
        'label' => 'Neutral Footer block',
        'provider' => 'oe_corporate_blocks',
        'label_display' => '0',
      ],
    ]);
    $entity->save();

    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);

    return new Crawler($render->__toString());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestGraphInfo(string $base_url, string $test): array {
    $module_path = \Drupal::service('extension.list.module')->getPath('rdf_skos');

    return [
      'fruit' => [
        'uri' => "http://example.com/fruit/$test",
        'data' => "$base_url/$module_path/tests/test_rdf/fruit.rdf",
      ],
      'vegetables' => [
        'uri' => "http://example.com/vegetables/$test",
        'data' => "$base_url/$module_path/tests/test_rdf/vegetables.rdf",
      ],
    ];
  }

}
