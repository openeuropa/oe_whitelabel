<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\node\Entity\Node;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test content language switcher rendering.
 */
class ContentLanguageSwitcherTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'content_translation',
    'daterange_compact',
    'language',
    'locale',
    'node',
    'oe_bootstrap_theme_helper',
    'oe_corporate_blocks',
    'oe_multilingual',
    'oe_whitelabel_helper',
    'oe_whitelabel_multilingual',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user']);

    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);

    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installConfig([
      'content_translation',
      'language',
      'locale',
      'oe_multilingual',
      'oe_whitelabel_helper',
      'system',
    ]);
    $this->container->get('module_handler')->loadInclude('oe_multilingual', 'install');
    oe_multilingual_install(FALSE);

    $this->container->get('theme_installer')->install(['oe_whitelabel']);
    $this->config('system.theme')->set('default', 'oe_whitelabel')->save();

    // Call the installation hook of the User module which creates the
    // Anonymous user and User 1. This is needed because the Anonymous user
    // is loaded to provide the current User context which is needed
    // in places like route enhancers.
    // @see CurrentUserContext::getRuntimeContexts().
    // @see EntityConverter::convert().
    module_load_include('install', 'user');
    user_install();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Tests the rendering of the language switcher block.
   */
  public function testMultilingualLanguageSwitcherBlockRendering(): void {
    $node = Node::create([
      'title' => 'Hello, world!',
      'type' => 'oe_demo_translatable_page',
    ]);
    /** @var \Drupal\Core\Entity\EntityInterface $translation */
    $node->addTranslation('es', ['title' => '¡Hola mundo!'])->save();

    // Simulate a request to the canonical route of the node with Bulgarian
    // language prefix.
    $this->setCurrentRequest('/bg/node/' . $node->id());

    // Setup and render language switcher block.
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [
      'id' => 'oe_multilingual_content_language_switcher',
      'label' => 'Content language switcher',
      'provider' => 'oe_multilingual',
      'label_display' => '0',
    ];

    /** @var \Drupal\Core\Block\BlockBase $plugin_block */
    $plugin_block = $block_manager->createInstance('oe_multilingual_content_language_switcher', $config);
    $render = $plugin_block->build();

    $html = (string) $this->container->get('renderer')->renderRoot($render);
    $crawler = new Crawler($html);

    // Make sure that content language switcher block is present.
    $actual = $crawler->filter('div.collapse.mt-3');
    $this->assertCount(1, $actual);

    // Warning message doesn't contain the unavailable language, the translation
    // will have it.
    $this->assertUnavailableLanguage($crawler, 'This page is not available in български.');

    // Make sure that selected language is properly rendered.
    $this->assertSelectedLanguage($crawler, 'English');

    // Make sure that available languages are properly rendered.
    $this->assertTranslationLinks($crawler, ['español']);

    // Remove the spanish translation.
    $node->removeTranslation('es');
    $node->save();

    // Re-render the block assuming a request to the Spanish version of the
    // node.
    $this->setCurrentRequest('/es/node/' . $node->id());
    $render = $plugin_block->build();

    $html = (string) $this->container->get('renderer')->renderRoot($render);
    $crawler = new Crawler($html);

    // Verify that the requested language is set as unavailable.
    $this->assertUnavailableLanguage($crawler, 'This page is not available in español.');

    // Verify that the content has been rendered in the fallback language.
    $this->assertSelectedLanguage($crawler, 'English');

    // Make sure that no language links are rendered.
    $this->assertTranslationLinks($crawler, []);
  }

  /**
   * Asserts that a language is marked as the current rendered.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The content language block crawler.
   * @param string $expected
   *   The label of the language.
   */
  protected function assertSelectedLanguage(Crawler $crawler, string $expected): void {
    // The selected language link will contain a svg, so we target that.
    $actual = $crawler->filter('div.collapse.mt-3 > div > a > svg')->parents()->first()->text();
    $this->assertEquals($expected, trim($actual));
  }

  /**
   * Asserts that a language is marked as unavailable.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The content language block crawler.
   * @param string $expected
   *   The label of the language.
   */
  protected function assertUnavailableLanguage(Crawler $crawler, string $expected): void {
    $actual = $crawler->filter('div.alert div.alert-content')->text();
    $this->assertStringContainsString($expected, trim($actual));
  }

  /**
   * Asserts the rendered translation links in the content language switcher.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The content language block crawler.
   * @param array $expected
   *   The labels of the translations that should be rendered as links.
   */
  protected function assertTranslationLinks(Crawler $crawler, array $expected): void {
    $elements = $crawler->filter('div.collapse.mt-3 > div > a');
    // Filter out the selected language.
    $elements = $elements->reduce(function (Crawler $crawler) {
      return $crawler->filter('svg')->count() === 0;
    });
    $this->assertSameSize($expected, $elements);

    $actual = array_column(iterator_to_array($elements), 'nodeValue');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Sets a request to a certain URI as the current in the request stack.
   *
   * @param string $uri
   *   The URI of the request. It needs to match a valid Drupal route.
   */
  protected function setCurrentRequest(string $uri): void {
    // Simulate a request to a node canonical route with a language prefix.
    $request = Request::create($uri);
    // Let the Drupal router populate all the request parameters.
    $parameters = \Drupal::service('router.no_access_checks')->matchRequest($request);
    $request->attributes->add($parameters);
    // Set the prepared request as current.
    \Drupal::requestStack()->push($request);
    // Reset any discovered language. KernelTestBase creates a request to the
    // root of the website for legacy purposes, so the language is set by
    // default to the default one.
    // @see \Drupal\KernelTests\KernelTestBase::bootKernel()
    \Drupal::languageManager()->reset();
  }

}
