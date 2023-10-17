<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay;

use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * @LinkDisplay(
 *   id = "entity_view_display",
 *   label = @Translation("Entity view display"),
 *   bundles = { "dynamic", "manual" },
 *   deriver = "Drupal\oe_whitelabel_link_lists\Plugin\LinkDisplay\EntityViewDisplayDeriver"
 * )
 */
class EntityViewDisplay extends LinkDisplayPluginBase {

}
