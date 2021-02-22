<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\data_provider\Annotation\DataProviderFetcher;
use Drupal\data_provider\Contracts\DataProviderFetcherInterface;
use Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface;

/**
 * Define the data provide fetcher plugin manager.
 */
class DataProviderFetcherManager extends DataProviderDefaultPluginManager implements DataProviderFetcherManagerInterface {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/DataProvider/Fetcher',
      $namespaces,
      $module_handler,
      DataProviderFetcherInterface::class,
      DataProviderFetcher::class
    );
    $this->alterInfo('data_provider_fetcher_info');
    $this->setCacheBackend($cache_backend, 'data_provider_fetcher');
  }

}
