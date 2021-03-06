<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the EU and the EC corporate Footer blocks rendering.
 */
class FooterBlockTest extends SparqlKernelTestBase {

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
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // For now we assert only minimal till we have a footer component.
    $this->assertCount(1, $crawler->filter('footer.bcl-footer--ec'));
    $rows = $crawler->filter('.row');
    $this->assertCount(2, $rows);
    $borderedSections = $crawler->filter('.bcl-footer__bordered-row');
    $this->assertCount(1, $borderedSections);
    $sectionTitles = $crawler->filter('p.fw-bold.mb-2');
    $this->assertCount(2, $sectionTitles);
    $sectionLinks = $crawler->filter('div.col-12.col-lg-4:nth-child(2) .mb-1 a.standalone');
    $this->assertCount(3, $sectionLinks);
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
    $this->assertCount(10, $sectionLinks);
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testNeutralFooterBlockRendering(): void {
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
    $crawler = new Crawler($render->__toString());

    // For now we assert only minimal till we have a footer component.
    $this->assertCount(1, $crawler->filter('footer.bcl-footer--neutral'));
    $rows = $crawler->filter('.row');
    $this->assertCount(1, $rows);
    $sectionTitles = $crawler->filter('p.fw-bold.mb-2');
    $this->assertCount(1, $sectionTitles);
  }

}
