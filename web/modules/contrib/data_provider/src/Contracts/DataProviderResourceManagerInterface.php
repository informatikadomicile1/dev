<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

/**
 * Define the data provider resource manager interface.
 */
interface DataProviderResourceManagerInterface {

  /**
   * Fetch the data provider resource output by name.
   *
   * @param string $name
   *   The data provider resource name.
   *
   * @return array
   *   The data provider resource transformation output.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function fetchByName(string $name): array;

  /**
   * Fetch the data provider resource output.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderResourceInterface $resource
   *   The data provider resource instance.
   *
   * @return array
   *   The data provider resource transformation output.
   */
  public function fetch(DataProviderResourceInterface $resource): array;

}
