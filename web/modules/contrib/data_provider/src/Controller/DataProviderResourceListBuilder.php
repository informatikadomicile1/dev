<?php

declare(strict_types=1);

namespace Drupal\data_provider\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * Define the data provider resource list builder.
 */
class DataProviderResourceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritDoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Label'),
      'fetcher' => $this->t('Fetcher Plugin'),
      'caching' => $this->t('Caching Enabled'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritDoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\data_provider\Entity\DataProviderResource $entity */
    return [
      $entity->label(),
      $entity->fetcherPluginId(),
      $entity->cachingEnabled() ? $this->t('Yes') : $this->t('No'),
    ] + parent::buildRow($entity);
  }

}
