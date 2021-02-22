<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\data_provider\Contracts\DataProviderFetcherResponseInterface;

/**
 * Define the data provider fetcher response.
 */
class DataProviderFetcherResponse implements DataProviderFetcherResponseInterface {

  /**
   * The plugin identifier.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The fetcher response.
   *
   * @var mixed
   */
  protected $response;

  /**
   * Constructor for the data provider fetcher response.
   *
   * @param mixed $response
   *   The fetcher plugin response.
   * @param string $plugin_id
   *   The fetcher plugin ID.
   */
  public function __construct($response, string $plugin_id) {
    $this->response = $response;
    $this->pluginId = $plugin_id;
  }

  /**
   * {@inheritDoc}
   */
  public function getPluginId(): string {
    return $this->pluginId;
  }

  /**
   * {@inheritDoc}
   */
  public function getResponse() {
    return $this->response;
  }

}
