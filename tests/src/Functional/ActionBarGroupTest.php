<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Functional;

use Drupal\Tests\oe_whitelabel\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the action bar field group.
 */
class ActionBarGroupTest extends WhitelabelBrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'oe_whitelabel_field_test',
    'oe_whitelabel_starter_event',
    'oe_whitelabel_starter_news',
    'oe_whitelabel_starter_person',
    'oe_whitelabel_starter_publication',
    'oe_whitelabel_extra_project',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::moduleHandler()->loadInclude('field_group', 'inc', 'includes/field_ui');
    // The extra fields declared in oe_whitelabel_field_test do not show
    // up until the cache is cleared.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Tests the action bar group in content types.
   */
  public function testActionBarGroup(): void {
    $bundles = [
      'Event',
      'News',
      'Person',
      'Publication',
      'Project',
    ];

    // Improve test speed by looping and catching exceptions instead of
    // installing multiple times. Each installation takes around 30 seconds,
    // so with 5 content types we save two minutes already.
    $errors = [];
    foreach ($bundles as $bundle_nice_name) {
      try {
        $node = call_user_func([$this, "create{$bundle_nice_name}Node"]);

        // Test that the action group is empty by default.
        $this->assertEmpty($this->renderNode($node)->filter('.action-bar'));

        // Place one field in the group.
        $this->addExtraFieldToActionBarGroup('oe_wt_field_test_string', $node->bundle());
        $crawler = $this->renderNode($node);
        $this->assertEquals('The OpenEuropa Initiative.', $crawler->filter('.action-bar')->text());

        // Add another field.
        $this->addExtraFieldToActionBarGroup('oe_wt_field_test_html_multiple', $node->bundle());
        $crawler = $this->renderNode($node);
        $fields = $crawler->filter('.action-bar > div');
        $this->assertCount(2, $fields);
        $this->assertEquals('The OpenEuropa Initiative.', $fields->eq(0)->text());
        // The second extra field returns 2 items.
        $items = $fields->eq(1)->children();
        $this->assertCount(2, $items);
        $this->assertStringContainsString('<span>First line with <b>markup</b>.</span>', $items->eq(0)->html());
        $this->assertStringContainsString('<span>Second line with <b>markup</b>.</span>', $items->eq(1)->html());
      }
      catch (\Exception $exception) {
        $errors[$bundle_nice_name] = $exception;
      }
    }

    if (!empty($errors)) {
      $message = '';
      foreach ($errors as $bundle => $exception) {
        $message .= sprintf('Failed assertion for %s: %s%s', strtolower($bundle), $exception->getMessage(), PHP_EOL);
      }
      $this->fail($message);
    }
  }

  /**
   * Renders the content banner view mode for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   A crawler around the rendered HTML.
   */
  protected function renderNode(NodeInterface $node): Crawler {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $view_builder->view($node, 'oe_w_content_banner');
    $html = (string) $this->container->get('renderer')->renderRoot($build);

    return new Crawler($html);
  }

  /**
   * Adds an extra field to the action bar group.
   *
   * @param string $extra_field_name
   *   The extra field name.
   * @param string $bundle
   *   The node bundle.
   */
  protected function addExtraFieldToActionBarGroup(string $extra_field_name, string $bundle): void {
    $display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $display_storage->load("node.$bundle.oe_w_content_banner");
    $extra_field_name = 'extra_field_' . $extra_field_name;
    $display->setComponent($extra_field_name, [
      'region' => 'content',
    ]);

    $groups = field_group_info_groups('node', $bundle, field_group_get_context_from_display($display), 'oe_w_content_banner');
    $this->assertArrayHasKey('group_action_bar', $groups);
    $group = $groups['group_action_bar'];
    $group->children = array_merge($group->children, [
      $extra_field_name,
    ]);
    field_group_group_save($group, $display);
  }

}
