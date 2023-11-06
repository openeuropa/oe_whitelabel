<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

/**
 * Teaser display for link lists.
 *
 * @LinkDisplay(
 *   id = "teaser",
 *   label = @Translation("Teaser"),
 *   description = @Translation("Display a Link lists using Teaser view display."),
 *   bundles = { "dynamic", "manual" }
 * )
 */
class TeaserDisplayPlugin extends ColumnLinkDisplayPluginBase {

}
