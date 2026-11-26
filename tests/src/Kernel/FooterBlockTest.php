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
    $this->createSocialLink('Mastodon', 'https://social.example.com/@example', 'mastodon');

    // Since Drupal 11.4, resetCache() with no arguments does not work for
    // blocks (issue #3341042). It tries to invalidate
    // BlockViewBuilder::getCacheTags(), but this is now empty. If we pass
    // the entity instead, resetCache() invalidates $entity->getCacheTags(),
    // which is 'config:block_list'. The page has this tag, so it works.
    // @see https://www.drupal.org/project/drupal/issues/3341042
    $builder->resetCache([$entity]);
    $build = $builder->view($entity, 'block');
    $crawler = new Crawler((string) $this->container->get('renderer')->renderRoot($build));

    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(1, $accessibility_link);
    $this->assertEquals('Accessibility', $accessibility_link->text());
    $this->assertSocialMediaRow($crawler);
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
    $this->createSocialLink('Mastodon', 'https://social.example.com/@example', 'mastodon');

    // @see https://www.drupal.org/project/drupal/issues/3341042
    $builder->resetCache([$entity]);
    $build = $builder->view($entity, 'block');
    $crawler = new Crawler((string) $this->container->get('renderer')->renderRoot($build));

    $accessibility_eu_link = $crawler->filter('a[href="https://european-union.europa.eu/accessibility-statement_en"]');
    $this->assertCount(0, $accessibility_eu_link);
    $accessibility_link = $crawler->filter('a[href="https://example.com/accessibility"]');
    $this->assertCount(1, $accessibility_link);
    $this->assertEquals('Accessibility statement', $accessibility_link->text());
    $this->assertSocialMediaRow($crawler);
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
   * Tests the site specific social media links in the neutral footer.
   */
  public function testNeutralFooterSocialMediaLinks(): void {
    $this->createSocialLink('LinkedIn', 'https://www.linkedin.com/company/example', 'linkedin', 1);
    $this->createSocialLink('Mastodon', 'https://social.example.com/@example', 'mastodon', 0);
    $this->createSocialLink('Other', '/social-media', 'chain', 2);
    $settings = \Drupal::configFactory()->getEditable('oe_corporate_blocks.social_media_footer_links_settings');

    // Horizontal row, icons only.
    $settings->set('alignment', 'horizontal')->set('display_labels', FALSE)->save();
    $crawler = $this->renderNeutralFooterBlock();
    $this->assertCount(2, $crawler->filter('.row'));
    $this->assertStringNotContainsString('Follow us on social media', $crawler->filter('footer')->text());
    // The second column is filled first.
    $columns = $crawler->filter('.row')->eq(0)->filter('div.col-12.col-lg-4');
    $this->assertEquals('EC Core column', $columns->eq(1)->filter('p.fw-bold')->text());
    $this->assertEmpty(trim($columns->eq(2)->text()));
    $social_row = $crawler->filter('.row')->eq(1)->filter('.col-12 .bcl-footer__social-media-follow');
    $this->assertCount(1, $social_row);
    $this->assertEquals('Follow us', $social_row->filter('p.fw-bold')->text());
    $links = $social_row->filter('li a.standalone');
    $this->assertCount(3, $links);
    $this->assertEquals('https://social.example.com/@example', $links->eq(0)->attr('href'));
    $this->assertEquals('Mastodon', $links->eq(0)->filter('.visually-hidden')->text());
    $this->assertStringEndsWith('#mastodon', $links->eq(0)->filter('use')->attr('xlink:href'));
    $this->assertEquals('https://www.linkedin.com/company/example', $links->eq(1)->attr('href'));
    $this->assertEquals('LinkedIn', $links->eq(1)->filter('.visually-hidden')->text());
    $this->assertStringEndsWith('#linkedin', $links->eq(1)->filter('use')->attr('xlink:href'));
    $this->assertEquals('/social-media', $links->eq(2)->attr('href'));
    $this->assertEquals('Other', $links->eq(2)->filter('.visually-hidden')->text());
    $this->assertStringEndsWith('#link', $links->eq(2)->filter('use')->attr('xlink:href'));

    // Visible labels.
    $settings->set('display_labels', TRUE)->save();
    $crawler = $this->renderNeutralFooterBlock();
    $links = $crawler->filter('.bcl-footer__social-media-follow li a.standalone');
    $this->assertCount(3, $links);
    $this->assertCount(0, $links->filter('.visually-hidden'));
    $this->assertCount(3, $links->filter('span.d-none.d-lg-inline'));
    $this->assertEquals('Mastodon', trim($links->eq(0)->text()));
    $this->assertEquals('Mastodon', $links->eq(0)->attr('aria-label'));
    $this->assertEquals('LinkedIn', trim($links->eq(1)->text()));
    $this->assertEquals('LinkedIn', $links->eq(1)->attr('aria-label'));
    $this->assertEquals('Other', trim($links->eq(2)->text()));
    $this->assertEquals('Other', $links->eq(2)->attr('aria-label'));

    // Vertical alignment keeps the legacy section.
    $settings->set('alignment', 'vertical')->save();
    $crawler = $this->renderNeutralFooterBlock();
    $this->assertCount(1, $crawler->filter('.row'));
    $this->assertCount(0, $crawler->filter('.bcl-footer__social-media-follow'));
    $this->assertStringContainsString('Follow us on social media', $crawler->filter('footer')->text());
    $columns = $crawler->filter('.row')->eq(0)->filter('div.col-12.col-lg-4');
    $this->assertEquals('Follow us on social media', $columns->eq(1)->filter('p.fw-bold')->text());
    $links = $columns->eq(1)->filter('a.standalone');
    $this->assertStringEndsWith('#mastodon', $links->eq(0)->filter('use')->attr('xlink:href'));
    $this->assertStringEndsWith('#linkedin', $links->eq(1)->filter('use')->attr('xlink:href'));
    $this->assertStringEndsWith('#link', $links->eq(2)->filter('use')->attr('xlink:href'));
    $this->assertEquals('EC Core column', $columns->eq(2)->filter('p.fw-bold')->text());

    // Without settings, the legacy section is shown.
    $settings->delete();
    $crawler = $this->renderNeutralFooterBlock();
    $this->assertCount(1, $crawler->filter('.row'));
    $this->assertCount(0, $crawler->filter('.bcl-footer__social-media-follow'));
    $this->assertStringContainsString('Follow us on social media', $crawler->filter('footer')->text());
  }

  /**
   * Creates a site specific social media link.
   *
   * @param string $label
   *   The link label.
   * @param string $url
   *   The link URL.
   * @param string $network
   *   The social network.
   * @param int $weight
   *   The link weight.
   */
  protected function createSocialLink(string $label, string $url, string $network, int $weight = 0): void {
    $this->container->get('entity_type.manager')->getStorage('footer_link_social')->create([
      'id' => strtolower($label),
      'label' => $label,
      'url' => $url,
      'social_network' => $network,
      'weight' => $weight,
    ])->save();
  }

  /**
   * Asserts the social media row is between the sections and the bordered row.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The rendered footer.
   */
  protected function assertSocialMediaRow(Crawler $crawler): void {
    $rows = $crawler->filter('.row');
    $this->assertCount(3, $rows);
    $this->assertCount(1, $rows->eq(1)->filter('.bcl-footer__social-media-follow'));
    $this->assertEquals('Follow us', $rows->eq(1)->filter('p.fw-bold')->text());
    $this->assertStringContainsString('bcl-footer__bordered-row', $rows->eq(2)->attr('class'));
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
    $entity = $entity_type_manager->load('neutralfooterblock');
    if (!$entity) {
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
    }

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
