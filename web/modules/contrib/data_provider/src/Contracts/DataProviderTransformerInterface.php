<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

/**
 * Define the data provider transformer plugin interface.
 */
interface DataProviderTransformerInterface {

  /**
   * Transform the data provided value.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderTransformerDataInterface $data
   *   The data that should be transformed.
   *
   * @return array
   *   The transformed value.
   */
  public function transform(DataProviderTransformerDataInterface $data): array;

  /**
   * Determine if the data provider value should be transformed.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderTransformerDataInterface $data
   *   The data transformed data object.
   *
   * @return bool
   *   Return TRUE if value is applicable; otherwise FALSE.
   */
  public function isApplicable(DataProviderTransformerDataInterface $data): bool;

}
