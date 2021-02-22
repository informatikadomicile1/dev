<?php

declare(strict_types=1);

namespace Drupal\data_provider\Contracts;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurableInterface;

/**
 * Define the data provider plugin interface.
 */
interface DataProviderDefaultPluginInterface extends PluginFormInterface, ConfigurableInterface {}
