<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Define the data provider resource interface.
 */
interface DataProviderResourceInterface extends ConfigEntityInterface {

  /**
   * Get the resource fetcher plugin ID.
   *
   * @return string
   *   The fetcher plugin ID.
   */
  public function fetcherPluginId(): ?string;

  /**
   * Get the resource fetcher plugin settings.
   *
   * @return array
   *   An array of the fetcher plugin settings.
   */
  public function fetcherSettings(): array;

  /**
   * Get the resource transformer plugins.
   *
   * @return array
   *   An array of transformer plugins.
   */
  public function transformerPlugins(): array;

  /**
   * Get the resource transformer plugin count.
   *
   * @return int
   *   Return the plugin count.
   */
  public function transformerPluginsCount(): int;

  /**
   * Caching is enabled.
   *
   * @return bool
   *   Return TRUE if caching is enabled; otherwise FALSE.
   */
  public function cachingEnabled(): bool;

  /**
   * Caching resource expired datetime.
   *
   * @return string|null
   *   A textual datetime string (+1day, now, etc).
   */
  public function cachingExpired(): ?string;

  /**
   * Caching resource expired timestamp.
   *
   * @return CacheBackendInter|int
   *   The cached expired timestamp; otherwise
   *   \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT.
   */
  public function cachingExpiredTimestamp(): int;

  /**
   * Caching recursive referenced entities.
   *
   * @return bool
   *   Return TRUE if caching is recursive, otherwise FALSE.
   */
  public function cachingRecursive(): bool;

  /**
   * Caching exclude entity types.
   *
   * @return array
   *   An array of the excluded entity types.
   */
  public function cachingExcludeEntityTypes(): array;

  /**
   * Caching resource caching tags.
   *
   * @return array
   *   An array of the resource caching tags.
   */
  public function resourceCachingTags(): array;

}
