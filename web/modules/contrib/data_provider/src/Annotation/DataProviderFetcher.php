<?php

declare(strict_types=1);

namespace Drupal\data_provider\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define the data provider fetcher plugin.
 *
 * @Annotation
 */
class DataProviderFetcher extends Plugin {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

}
