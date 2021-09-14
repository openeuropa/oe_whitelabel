<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the OE Authentication LoginBlock rendering.
 */
class SearchFacetsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'cas',
    'components',
    'externalauth',
    'oe_authentication',
    'ui_patterns',
    'ui_patterns_library',
    'user',
    'system',
    'search_api',
    'facets',
    'facets_summary',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    \Drupal::service('theme_installer')->install(['oe_whitelabel']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();

    $this->container->set('theme.registry', NULL);
    $this->container->get('cache.render')->deleteAll();
    $this->installEntitySchema('facets_facet');
    $this->installEntitySchema('facets_summary');
  }

  /**
   * Tests facets summary.
   *
   * @covers ::setFacets
   * @covers ::getFacets
   * @covers ::removeFacet
   */
  public function testFacets() {
    $entity = new FacetsSummary(['description' => 'Owls', 'name' => 'owl'], 'facets_summary');

    $this->assertEmpty($entity->getFacets());

    $facets = ['foo' => 'bar'];
    $entity->setFacets($facets);
    $this->assertEquals($facets, $entity->getFacets());

    $entity->removeFacet('foo');
    $this->assertEmpty($entity->getFacets());
  }

}
