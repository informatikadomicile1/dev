<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Define the data provider fetcher manager interface.
 */
interface DataProviderFetcherManagerInterface extends PluginManagerInterface {

  /**
   * Get the plugin options.
   *
   * @return array
   *   An array of plugin options.
   */
  public function getOptions(): array;

}
