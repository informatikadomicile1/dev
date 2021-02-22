<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

use Drupal\data_provider\DataProviderFetcherResponse;

/**
 * Define the data provider fetcher plugin interface.
 */
interface DataProviderFetcherInterface {

  /**
   * Fetch the data response.
   *
   * @return \Drupal\data_provider\DataProviderFetcherResponse
   *   The data provider fetched response.
   */
  public function fetch(): DataProviderFetcherResponse;

}
