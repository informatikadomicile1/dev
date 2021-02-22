<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

/**
 * Define the data provider fetcher response interface.
 */
interface DataProviderFetcherResponseInterface {

  /**
   * Get fetch plugin identifier.
   *
   * @return string
   *   The plugin identifier.
   */
  public function getPluginId(): string;

  /**
   * Get the fetched response.
   *
   * @return mixed
   *   The fetched response.
   */
  public function getResponse();

}
