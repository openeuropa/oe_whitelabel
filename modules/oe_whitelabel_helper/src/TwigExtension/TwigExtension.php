<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\TwigExtension;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Collection of extra Twig extensions as filters and functions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * The plugin.manager.block service.
   *
   * @var \Drupal\Core\Cache\CacheableDependencyInterface
   */
  protected $pluginManagerBlock;

  /**
   * Constructs the TwigExtension object.
   */
  public function __construct(CacheableDependencyInterface $plugin_manager_block) {
    $this->pluginManagerBlock = $plugin_manager_block;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('bcl_footer_links', [$this, 'bclFooterLinks'], ['needs_context' => TRUE]),
      new TwigFunction('bcl_block', [$this, 'bclBlock']),
    ];
  }

  /**
   * Processes footer links to make them compatible with BCL formatting.
   *
   * @param array $context
   *   The twig context.
   * @param array $links
   *   Set of links to be processed.
   *
   * @return array
   *   Set of processed links.
   */
  public function bclFooterLinks(array $context, array $links): array {
    $altered_links = [];

    foreach ($links as $link) {
      $altered_link = [
        'label' => $link['label'],
        'path' => $link['href'],
        'icon_position' => 'after',
        'attributes' => [
          'class' => [
            'text-decoration-none',
            'd-block',
            'mb-1',
          ],
        ],
      ];

      if (!empty($link['external']) && $link['external'] === TRUE) {
        $altered_link['icon'] = [
          'path' => $context['bcl_icon_path'],
          'name' => 'external',
          'size' => 'xs',
        ];
      }

      if (!empty($link['social_network'])) {
        $altered_link['icon_position'] = 'before';
        $altered_link['icon'] = [
          'path' => $context['bcl_icon_path'],
          'name' => $link['social_network'],
        ];
      }

      $altered_links[] = $altered_link;
    }

    return $altered_links;
  }

  /**
   * Builds the render array for a block.
   *
   * @param string $id
   *   The block plugin ID.
   * @param array $configuration
   *   The block configuration.
   *
   * @return array
   *   The block render array.
   */
  public function bclBlock(string $id, array $configuration = []): array {
    $configuration += ['label_display' => 'hidden'];

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $this->pluginManagerBlock->createInstance($id, $configuration);

    return $block_plugin->build();
  }

}
