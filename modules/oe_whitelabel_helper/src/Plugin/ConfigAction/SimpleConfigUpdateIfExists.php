<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates simple configuration only if the target config exists.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'simpleConfigUpdateIfExists',
  admin_label: new TranslatableMarkup('Simple configuration update if exists'),
)]
final class SimpleConfigUpdateIfExists implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $config = $this->configFactory->getEditable($configName);
    if ($config->isNew()) {
      return;
    }

    if (!is_array($value)) {
      throw new ConfigActionException(sprintf('Config %s can not be updated because $value is not an array', $configName));
    }

    foreach ($value as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();
  }

}
