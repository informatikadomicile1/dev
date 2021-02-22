<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\data_provider\Contracts\DataProviderDefaultPluginInterface;

/**
 * Define the data provider plugin base class.
 */
abstract class DataProviderPluginBase extends PluginBase implements DataProviderDefaultPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration): self {
    $this->configuration = $configuration;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration(): array {
    return $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    // Intentionally left empty as validation is not required.
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $this->setConfiguration($form_state->cleanValues()->getValues());
  }

}
