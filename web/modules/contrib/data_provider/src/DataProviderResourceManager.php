<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\data_provider\Contracts\DataProviderResourceInterface;
use Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface;
use Drupal\data_provider\Contracts\DataProviderResourceManagerInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerDataInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface;

/**
 * Define the data provide resource manager.
 */
class DataProviderResourceManager implements DataProviderResourceManagerInterface {

  use UseCacheBackendTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Fetcher plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fetcherPluginManager;

  /**
   * Transformer plugin manager.
   *
   * @var \Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface
   */
  protected $transformerPluginManager;

  /**
   * Constructor for the data provider resource manager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The caching backend instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface $fetcher_plugin_manager
   *   The data provider fetcher manager service.
   * @param \Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface $transformer_plugin_manager
   *   The data provider transformer manager service.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    EntityTypeManagerInterface $entity_type_manager,
    DataProviderFetcherManagerInterface $fetcher_plugin_manager,
    DataProviderTransformerManagerInterface $transformer_plugin_manager
  ) {
    $this->cacheBackend = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->fetcherPluginManager = $fetcher_plugin_manager;
    $this->transformerPluginManager = $transformer_plugin_manager;
  }

  /**
   * Determine if the cache should be used.
   *
   * @param bool $use_caches
   *   Determine if cache should be used.
   */
  public function useCaches($use_caches = FALSE): void {
    $this->useCaches = $use_caches;
  }

  /**
   * {@inheritDoc}
   */
  public function fetchByName(string $name): array {
    $resource = $this->loadDataProviderResource($name);

    if (!$resource instanceof DataProviderResourceInterface) {
      throw new \RuntimeException(sprintf(
        'Unable to locate data provider for %s!',
        $name
      ));
    }

    return $this->fetch($resource);
  }

  /**
   * {@inheritDoc}
   */
  public function fetch(DataProviderResourceInterface $resource): array {
    try {
      $cid = "data_provider:response:{$resource->id()}";

      if ($resource->cachingEnabled() && ($cache = $this->cacheGet($cid))) {
        return $cache->data;
      }

      if ($response = $this->doFetch($resource)) {
        $data = $this->doTransformation($resource, new DataProviderTransformData(
          $response->getResponse()
        ));

        if ($resource->cachingEnabled()) {
          $this->cacheSet(
            $cid,
            $data,
            $resource->cachingExpiredTimestamp(),
            $resource->getCacheTags()
          );
        }

        return $data;
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('data_provider', $exception);
    }
  }

  /**
   * Conduct the fetching of the resource data.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderResourceInterface $resource
   *   The data provider resource instance.
   *
   * @return \Drupal\data_provider\DataProviderFetcherResponse
   *   The data provider fetcher response.
   */
  protected function doFetch(
    DataProviderResourceInterface $resource
  ): DataProviderFetcherResponse {
    try {
      /** @var \Drupal\data_provider\Contracts\DataProviderFetcherInterface $instance */
      $instance = $this->fetcherPluginManager->createInstance(
        $resource->fetcherPluginId(),
        $resource->fetcherSettings()
      );
      return $instance->fetch();
    }
    catch (\Exception $exception) {
      watchdog_exception('data_provider', $exception);
    }
  }

  /**
   * Conduct the transforming the resource data.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderResourceInterface $resource
   *   The data provider resource instance.
   * @param \Drupal\data_provider\Contracts\DataProviderTransformerDataInterface $data
   *   The data provider transformer data.
   *
   * @return array
   *   The resource fetched data transformation.
   */
  protected function doTransformation(
    DataProviderResourceInterface $resource,
    DataProviderTransformerDataInterface $data
  ): array {
    try {
      if ($transformer_plugin = $resource->transformerPlugins()) {
        foreach ($transformer_plugin as $transformer) {
          if (!isset($transformer['plugin_id'])) {
            continue;
          }

          /** @var \Drupal\data_provider\Contracts\DataProviderTransformerInterface $instance */
          $instance = $this->transformerPluginManager->createInstance(
            $transformer['plugin_id'],
            $transformer['settings'] ?? []
          );

          if (!$instance->isApplicable($data)) {
            continue;
          }
          $data = new DataProviderTransformData($instance->transform($data));
        }

        return $data->getValue();
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('data_provider', $exception);
    }

    return [];
  }

  /**
   * Load the data provider resource.
   *
   * @param string $identifier
   *   The data provide resource identifier.
   *
   * @return \Drupal\data_provider\Contracts\DataProviderResourceInterface
   *   The data provider resource instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadDataProviderResource(
    string $identifier
  ): DataProviderResourceInterface {
    return $this->dataProviderResourceStorage()->load($identifier);
  }

  /**
   * Data provider resource storage instance.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The data provider resource storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function dataProviderResourceStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('data_provider_resource');
  }

}
