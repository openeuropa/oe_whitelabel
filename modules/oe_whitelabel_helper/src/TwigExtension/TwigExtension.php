<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\TwigExtension;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Collection of extra Twig extensions as filters and functions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * The plugin.manager.block service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs the TwigExtension object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $plugin_manager_block
   *   The plugin.manager.block service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(BlockManagerInterface $plugin_manager_block, DateFormatterInterface $date_formatter) {
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('bcl_timeago', [$this, 'bclTimeAgo']),
    ];
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
   * Filters a timestamp in "time ago" format.
   *
   * @param string $timestamp
   *   Datetime to be parsed.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The translated time ago string.
   */
  public function bclTimeAgo(string $timestamp): FormattableMarkup {
    $result = $this->dateFormatter->formatTimeDiffSince($timestamp, [
      'granularity' => 1,
      'return_as_object' => TRUE,
    ]);

    return new FormattableMarkup('@interval ago', ['@interval' => $result->getString()]);
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
        'standalone' => TRUE,
        'attributes' => [
          'class' => [
            'd-block',
            'mb-1',
          ],
        ],
      ];

      if (!empty($link['external']) && $link['external'] === TRUE) {
        $altered_link['icon'] = [
          'path' => $context['bcl_icon_path'],
          'name' => 'box-arrow-up-right',
          'size' => 'xs',
        ];
      }

      if (!empty($link['social_network'])) {
        $altered_link['icon_position'] = 'before';
        $altered_link['icon'] = [
          'path' => $context['bcl_icon_path'],
          'name' => $link['social_network'],
          'size' => 'xs',
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
