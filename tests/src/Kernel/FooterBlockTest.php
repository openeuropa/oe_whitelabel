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
    'components',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_settings',
    'user',
    'system',
    'oe_whitelabel_helper',
    'oe_corporate_site_info',
    'oe_corporate_blocks',
    'rdf_skos',
    'multivalue_form_element',
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

    $this->container->set('theme.registry', NULL);
    $this->container->get('cache.render')->deleteAll();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testEcFooterBlockRendering(): void {
    $entity_type_manager = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');
    $entity = $entity_type_manager->create([
      'id' => 'ecfooterblock',
      'theme' => 'oe_whitelabel',
      'plugin' => 'oe_corporate_blocks_ec_footer',
      'settings' => [
        'id' => 'oe_corporate_blocks_ec_footer',
        'label' => 'EC Footer block',
        'provider' => 'oe_corporate_blocks',
        'label_display' => '0',
      ],
    ]);
    $entity->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $build = $builder->view($entity, 'block');
    $render = $this->container->get('renderer')->renderRoot($build);
    $crawler = new Crawler($render->__toString());

    // Assert the footer composition.
    $footer = $crawler->filter('footer');
    $this->assertCount(1, $footer);
    $this->assertSame('ec__footer bcl-footer mt-4', $footer->attr('class'));
    $container = $footer->filter('div.container');
    $this->assertCount(1, $container);
    $row = $container->filter('div.pt-4.pt-lg-5.row');
    $this->assertCount(1, $row);
    // Assert top section.
    $top_section = $row->filter('div.col-12.col-lg-4');
    $this->assertCount(1, $top_section);
    $p = $top_section->filter('p.fw-bold.mb-2');
    $this->assertCount(1, $p);
    // Assert middle section.
    $middle_section = $container->filter('div.pb-4.pb-lg-5.mt-4.mt-lg-5.bcl-footer__bordered-row.row');
    $this->assertCount(1, $middle_section);
    $col = $middle_section->filter('.col-12.col-lg-4');
    $this->assertCount(1, $col);
    $p = $col->filter('p.fw-bold.pb-2.mb-2');
    $this->assertSame(' More information on:', $p->text());
    $links = $col->filter('a.d-block.mb-1.text-underline-hover');
    $this->assertCount(17, $links);
    // Assert bottom section.
    $sections = $container->filter('div.pb-4.pb-lg-5.bcl-footer__bordered-row.row');
    // Bottom section classes are encompassed in middle section so we need
    // the second item.
    $bottom_section = $sections->eq(1);
    $this->assertCount(1, $bottom_section);
    $cols = $bottom_section->filter('.col-12.col-lg-4');
    $this->assertCount(3, $cols);
    $col1 = $cols->eq(0);
    $this->assertSame('col-12 col-lg-4 pb-lg-4', $col1->attr('class'));
    $p = $col1->filter('p.fw-bold.pb-2.mb-2');
    $this->assertSame(' European Commission ', $p->text());
    $col2 = $cols->eq(1);
    $links = $col2->filter('a.d-block.mb-1.text-underline-hover');
    $this->assertCount(3, $links);
    $col3 = $cols->eq(2);
    $links = $col3->filter('a.d-block.mb-1.text-underline-hover');
    $this->assertCount(4, $links);
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

    // Assert the footer composition.
    $footer = $crawler->filter('footer');
    $this->assertCount(1, $footer);
    $this->assertSame('bcl-footer mt-4', $footer->attr('class'));
    $container = $footer->filter('div.container');
    $this->assertCount(1, $container);
    $row = $container->filter('div.pt-4.pt-lg-5.row');
    $this->assertCount(1, $row);
    // Assert top section.
    $top_section = $row->filter('div.col-12.col-lg-4');
    $this->assertCount(1, $top_section);
    $p = $top_section->filter('p.fw-bold.mb-2');
    $this->assertCount(1, $p);
    // Assert middle section.
    $middle_section = $container->filter('div.pb-4.pb-lg-5.mt-4.mt-lg-5.bcl-footer__bordered-row.row');
    $this->assertCount(1, $middle_section);
    $col1 = $middle_section->filter('.col-12.col-lg-4.pb-4');
    $link = $col1->filter('a.navbar-brand');
    $this->assertCount(1, $link);
    $picture = $link->filter('picture');
    $this->assertCount(1, $picture);
    $img = $link->filter('img');
    $this->assertCount(1, $img);
    $this->assertStringContainsString('logo-eu--en.svg', $img->attr('src'));
    $cols = $middle_section->filter('.col-12.col-lg-4');
    $col2 = $cols->eq(1);
    $p = $col2->filter('p.fw-bold.border-bottom.pb-2.mb-2');
    $this->assertSame(' Contact the EU  ', $p->text());
    $titles = $col2->filter('p.fw-bold.border-bottom.pb-2.pt-3.mb-2');
    $this->assertCount(2, $titles);
    $p = $titles->eq(0);
    $this->assertSame(' Social media ', $p->text());
    $p = $titles->eq(1);
    $this->assertSame(' Legal ', $p->text());
    $links = $col2->filter('a.d-block.mb-1.text-underline-hover');
    $this->assertCount(10, $links);
    $col3 = $cols->eq(2);
    $p = $col3->filter('p.fw-bold.border-bottom.pb-2.mb-2');
    $this->assertSame(' EU institutions ', $p->text());
    $links = $col3->filter('a.d-block.mb-1.text-underline-hover');
    $this->assertCount(17, $links);
  }

}
