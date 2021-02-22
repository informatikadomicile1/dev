<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\data_provider\Contracts\DataProviderDefaultPluginManagerInterface;

/**
 * Define the data provider default plugin manager.
 */
abstract class DataProviderDefaultPluginManager extends DefaultPluginManager implements DataProviderDefaultPluginManagerInterface {

  /**
   * Get plugin definition options.
   *
   * @param callable|null $filter
   *   A filter callback which returns TRUE if option should be excluded.
   *
   * @return array
   *   An array of plugin definition options.
   */
  public function getOptions(?callable $filter = NULL): array {
    $options = [];

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (!isset($definition['label'])
        || (is_callable($filter) && $filter($definition))) {
        continue;
      }
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }

}
