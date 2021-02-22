<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin\DataProvider\Fetcher;

use Drupal\data_provider\Plugin\DataProviderPluginBase;
use Drupal\data_provider\Contracts\DataProviderFetcherInterface;

/**
 * Define the data provider fetcher plugin base class.
 */
abstract class DataProviderFetcherBase extends DataProviderPluginBase implements DataProviderFetcherInterface {}
