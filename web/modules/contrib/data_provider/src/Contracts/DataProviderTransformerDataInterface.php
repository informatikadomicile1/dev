<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

/**
 * Define the data provider transformer value.
 */
interface DataProviderTransformerDataInterface {

  /**
   * Get transformer value.
   *
   * @return mixed
   *   The transformer value.
   */
  public function getValue();

  /**
   * Set transformer value.
   *
   * @param mixed $value
   *   A dynamic value.
   *
   * @return $this
   */
  public function setValue($value): object;

}
