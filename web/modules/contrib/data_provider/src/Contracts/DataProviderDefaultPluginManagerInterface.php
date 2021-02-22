<?php

namespace Drupal\data_provider\Contracts;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Define the data provider default plugin manager interface.
 */
interface DataProviderDefaultPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get plugin definition options.
   *
   * @param callable|null $filter
   *   A filter callback which returns TRUE if option should be excluded.
   *
   * @return array
   *   An array of plugin definition options.
   */
  public function getOptions(?callable $filter = NULL): array;

}
