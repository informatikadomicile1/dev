<?php

declare(strict_types=1);

namespace Drupal\data_provider\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\data_provider\Contracts\DataProviderResourceInterface;

/**
 * Define the data provider configuration entity.
 *
 * @ConfigEntityType(
 *   id = "data_provider_resource",
 *   label = @Translation("Data Provider Resource"),
 *   label_plural = @Translation("Data Provider Resources"),
 *   label_singular = @Translation("Data Provider Resource"),
 *   label_collection = @Translation("Data Provider Resource"),
 *   admin_permission = "administer data provider resources",
 *   config_prefix = "resource",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "fetcher",
 *     "caching",
 *     "transformer"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\data_provider\Form\DataProviderResourceFormDefault",
 *       "edit" = "\Drupal\data_provider\Form\DataProviderResourceFormDefault",
 *       "delete" = "\Drupal\data_provider\Form\DataProviderResourceFormDelete",
 *       "default" = "\Drupal\data_provider\Form\DataProviderResourceFormDefault"
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *    "list_builder" = "\Drupal\data_provider\Controller\DataProviderResourceListBuilder"
 *   },
 *   links = {
 *     "collection" = "/admin/config/data-provider/resource",
 *     "add-form" = "/admin/config/data-provider/resource/add",
 *     "edit-form" = "/admin/config/data-provider/resource/{data_provider_resource}",
 *     "delete-form" = "/admin/config/data-provider/resource/{data_provider_resource}/delete"
 *   }
 * )
 */
class DataProviderResource extends ConfigEntityBase implements DataProviderResourceInterface {

  /**
   * The resource name.
   *
   * @var string
   */
  protected $name;

  /**
   * The resource label.
   *
   * @var string
   */
  protected $label;

  /**
   * The resource caching.
   *
   * @var array
   */
  protected $caching = [];

  /**
   * The resource fetchers.
   *
   * @var array
   */
  protected $fetcher = [];

  /**
   * The resource transformers.
   *
   * @var array
   */
  protected $transformer = [];

  /**
   * {@inheritDoc}
   */
  public function id(): ?string {
    return $this->name;
  }

  /**
   * {@inheritDoc}
   */
  public function fetcherPluginId(): ?string {
    return $this->fetcher['plugin_id'] ?? NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function fetcherSettings(): array {
    return $this->fetcher['settings'] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function transformerPlugins(): array {
    return $this->transformer['plugins'] ?: [];
  }

  /**
   * {@inheritDoc}
   */
  public function transformerPluginsCount(): int {
    return count($this->transformerPlugins());
  }

  /**
   * {@inheritDoc}
   */
  public function cachingEnabled(): bool {
    return (bool) ($this->caching['enabled'] ?? FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function cachingExpired(): ?string {
    return $this->caching['expired'] ?? NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function cachingExpiredTimestamp(): int {
    $expired = $this->cachingExpired();

    if (!isset($expired) || empty($expired)) {
      return CacheBackendInterface::CACHE_PERMANENT;
    }

    return (new DateTimePlus($expired))->getTimestamp();
  }

  /**
   * {@inheritDoc}
   */
  public function cachingRecursive(): bool {
    return (bool) ($this->caching['recursive'] ?? FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function cachingExcludeEntityTypes(): array {
    return $this->caching['exclude']['entity_types'] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function resourceCachingTags(): array {
    return array_filter(explode("\r\n", $this->caching['tags'] ?? ''));
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags(): array {
    $cache_tags = parent::getCacheTags();

    foreach ($this->resourceCachingTags() as $caching_tag) {
      [$entity_type_id, $entity_id] = explode(':', $caching_tag);

      try {
        $storage = $this->entityTypeManager()->getStorage($entity_type_id);

        if (($entity = $storage->load($entity_id)) && $entity instanceof EntityInterface) {
          $this->recursiveEntityCacheTags($entity, $cache_tags);
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('data_provider', $exception);
      }
    }

    return $cache_tags;
  }

  /**
   * Get recursive content entity cache tags.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A entity instance.
   * @param array $cache_tags
   *   An array of the cache tags.
   */
  protected function recursiveEntityCacheTags(
    EntityInterface $entity,
    array &$cache_tags
  ): void {
    if (!in_array($entity->getEntityTypeId(), $this->cachingExcludeEntityTypes(), TRUE)) {
      $cache_tags = Cache::mergeTags($entity->getCacheTags(), $cache_tags);

      if ($this->cachingRecursive()) {
        foreach ($entity->referencedEntities() as $referenced_entity) {
          if (!$referenced_entity instanceof ContentEntityInterface) {
            continue;
          }
          $this->recursiveEntityCacheTags($referenced_entity, $cache_tags);
        }
      }
    }
  }

}
