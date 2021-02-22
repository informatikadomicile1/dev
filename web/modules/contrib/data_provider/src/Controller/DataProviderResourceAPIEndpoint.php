<?php

declare(strict_types=1);

namespace Drupal\data_provider\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\data_provider\Contracts\DataProviderResourceInterface;
use Drupal\data_provider\Contracts\DataProviderResourceManagerInterface;

/**
 * Define the data provide resource API endpoints.
 */
class DataProviderResourceAPIEndpoint extends ControllerBase {

  /**
   * The resource manager.
   *
   * @var \Drupal\data_provider\Contracts\DataProviderResourceManagerInterface
   */
  protected $resourceManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('data_provider.resource.manager')
    );
  }

  /**
   * Data provider resource API endpoint constructor.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderResourceManagerInterface $resource_manager
   *   The data provider resource manager.
   */
  public function __construct(DataProviderResourceManagerInterface $resource_manager) {
    $this->resourceManager = $resource_manager;
  }

  /**
   * Get data provider transformed output.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderResourceInterface|null $resource
   *   The data provider resource.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response object containing the transformed output.
   */
  public function getResource(DataProviderResourceInterface $resource = NULL): Response {
    return new JsonResponse([
      'resource' => $resource->id(),
      'contents' => $this->resourceManager->fetch($resource),
    ]);
  }

  /**
   * Check data provide resource access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account service.
   * @param \Drupal\data_provider\Contracts\DataProviderResourceInterface|null $resource
   *   The data provider resource.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   */
  public function checkResourceAccess(
    AccountInterface $account,
    DataProviderResourceInterface $resource = NULL
  ) {
    if ($account->hasPermission('access all data provider resources')
      || $account->hasPermission("access data provider {$resource->id()} resource")) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
