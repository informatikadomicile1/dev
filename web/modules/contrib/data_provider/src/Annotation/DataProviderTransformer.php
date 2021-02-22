<?php

declare(strict_types=1);

namespace Drupal\data_provider\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define the data provider transformer plugin.
 *
 * @Annotation
 */
class DataProviderTransformer extends Plugin {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

  /**
   * @var bool
   */
  public $support_multiple = FALSE;

}
