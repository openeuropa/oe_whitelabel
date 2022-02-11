<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\TwigExtension;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\Url;
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
      new TwigFunction('bcl_title', [$this, 'bclTitle']),
    ];
  }

  /**
   * Filters a timestamp in "time ago" format.
   *
   * @param string $timestamp
   *   Datetime to be parsed.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   The translated time ago string.
   */
  public function bclTimeAgo(string $timestamp): PluralTranslatableMarkup {
    $time = \Drupal::time()->getCurrentTime() - $timestamp;
    $time_ago = new PluralTranslatableMarkup(0, 'N/A', 'N/A');
    $units = [
      31536000 => [
        'singular' => '@number year ago',
        'plural' => '@number years ago',
      ],
      2592000 => [
        'singular' => '@number month ago',
        'plural' => '@number months ago',
      ],
      604800 => [
        'singular' => '@number week ago',
        'plural' => '@number weeks ago',
      ],
      86400 => [
        'singular' => '@number day ago',
        'plural' => '@number days ago',
      ],
      3600 => [
        'singular' => '@number hour ago',
        'plural' => '@number hours ago',
      ],
      60 => [
        'singular' => '@number minute ago',
        'plural' => '@number minutes ago',
      ],
      1 => [
        'singular' => '@number second ago',
        'plural' => '@number seconds ago',
      ],
    ];

    foreach ($units as $unit => $format) {
      if ($time < $unit) {
        continue;
      }

      $number_of_units = floor($time / $unit);
      $time_ago = \Drupal::translation()
        ->formatPlural($number_of_units, $format['singular'], $format['plural'], ['@number' => $number_of_units]);
      break;
    }

    return $time_ago;
  }

  /**
   * Format footer section titles.
   *
   * @param string $title
   *   The title to be formatted.
   * @param string $classes
   *   Classes to add to the title.
   *
   * @return array
   *   Title prepared to be rendered in the footer template.
   */
  public function bclTitle(string $title, string $classes): array {
    $title = \Drupal::translation()->translate($title)->render();
    return ['#markup' => '<p class="' . $classes . '">' . $title . '</p>'];
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
      if ($link['href'] instanceof Url) {
        $link['href'] = $link['href']->toString();
      }
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
