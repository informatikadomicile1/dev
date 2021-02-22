<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\data_provider\Annotation\DataProviderTransformer;
use Drupal\data_provider\Contracts\DataProviderTransformerInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface;

/**
 * Define the data provide transformer plugin manager.
 */
class DataProviderTransformerManager extends DataProviderDefaultPluginManager implements DataProviderTransformerManagerInterface {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/DataProvider/Transformer',
      $namespaces,
      $module_handler,
      DataProviderTransformerInterface::class,
      DataProviderTransformer::class
    );
    $this->alterInfo('data_provider_transformer_info');
    $this->setCacheBackend($cache_backend, 'data_provider_transformer');
  }

}
