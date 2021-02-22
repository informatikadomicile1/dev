<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\data_provider\Contracts\DataProviderTransformerDataInterface;

/**
 * Define the data provider transformer value object.
 */
class DataProviderTransformData implements DataProviderTransformerDataInterface {

  /**
   * @var mixed
   */
  protected $value;

  /**
   * Data provider transformer value constructor.
   *
   * @param mixed $value
   *   The transformer value.
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritDoc}
   */
  public function setValue($value): object {
    $this->value = $value;

    return $this;
  }

}
