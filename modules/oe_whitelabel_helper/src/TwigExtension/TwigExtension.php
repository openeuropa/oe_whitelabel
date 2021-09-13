<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\TwigExtension;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Collection of extra Twig extensions as filters and functions.
 *
 * We don't enforce any strict type checking on filters' arguments as they are
 * coming straight from Twig templates.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new TwigExtension object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(LanguageManagerInterface $languageManager, RendererInterface $renderer) {
    $this->languageManager = $languageManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new \Twig_SimpleFilter('format_size', 'format_size'),
      new \Twig_SimpleFilter('to_language', [$this, 'toLanguageName']),
      new \Twig_SimpleFilter('to_native_language', [
        $this,
        'toNativeLanguageName',
      ]),
      new \Twig_SimpleFilter('to_internal_language_id', [
        $this,
        'toInternalLanguageId',
      ]),
      new \Twig_SimpleFilter('to_file_icon', [$this, 'toFileIcon']),
      new \Twig_SimpleFilter('to_date_status', [$this, 'toDateStatus']),
      new \Twig_SimpleFilter('to_ecl_attributes', [$this, 'toEclAttributes']),
      new \Twig_SimpleFilter('smart_trim', [$this, 'smartTrim'], ['needs_environment' => TRUE]),
      new \Twig_SimpleFilter('is_external_url', [UrlHelper::class, 'isExternal']),
      new \Twig_SimpleFilter('filter_empty', [$this, 'filterEmpty']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new \Twig_SimpleFunction('ecl_footer_links', [$this, 'eclFooterLinks'], ['needs_context' => TRUE]),
    ];
  }

  /**
   * Processes footer links to make them compatible with ECL formatting.
   *
   * @param array $context
   *   The twig context.
   * @param array $links
   *   Set of links to be processed.
   *
   * @return array
   *   Set of processed links.
   */
  public function eclFooterLinks(array $context, array $links): array {
    $ecl_links = [];

    foreach ($links as $link) {
      $ecl_link = [
        'link' => [
          'label' => $link['label'],
          'path' => $link['href'],
          'icon_position' => 'after',
        ],
      ];

      if (!empty($link['external']) && $link['external'] === TRUE) {
        $ecl_link += [
          'icon' => [
            'path' => $context['ecl_icon_path'],
            'name' => 'external',
            'size' => 'xs',
          ],
        ];
      }

      if (!empty($link['social_network'])) {
        $ecl_link['link']['icon_position'] = 'before';
        $ecl_link += [
          'icon' => [
            'path' => $context['ecl_icon_social_media_path'],
            'name' => $context['ecl_component_library'] == 'eu' ? $link['social_network'] : $link['social_network'] . '-negative',
          ],
        ];
      }

      $ecl_links[] = $ecl_link;
    }

    return $ecl_links;
  }

}
