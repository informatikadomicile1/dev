<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Define the data provider resource permissions.
 */
class DataProviderResourcePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Define the class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Create the data provider permissions.
   *
   * @return array
   *   An array of permission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function permissions(): array {
    $permissions = [];

    $resources = $this->entityTypeManager
      ->getStorage('data_provider_resource')
      ->loadMultiple();

    foreach ($resources as $resource) {
      $permissions["access data provider {$resource->id()} resource"] = [
        'title' => $this->t('Access data provider @name resource', [
          '@name' => $resource->id(),
        ]),
      ];
    }

    return $permissions;
  }

}
