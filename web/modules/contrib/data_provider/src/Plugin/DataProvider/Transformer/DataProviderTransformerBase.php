<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin\DataProvider\Transformer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\data_provider\Plugin\DataProviderPluginBase;
use Drupal\data_provider\Contracts\DataProviderTransformerInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerDataInterface;

/**
 * Define the data provider transformer plugin base.
 */
abstract class DataProviderTransformerBase extends DataProviderPluginBase implements DataProviderTransformerInterface {

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function isApplicable(
    DataProviderTransformerDataInterface $data
  ): bool {
    return TRUE;
  }

}
