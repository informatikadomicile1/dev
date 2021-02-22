<?php

declare(strict_types=1);

namespace Drupal\data_provider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Define the form state utility trait.
 */
trait FormStateUtilityTrait {

  /**
   * Get the form state element value.
   *
   * @param array|string $key
   *   An array of a nested structure or a single element key.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param null|mixed $default_value
   *   The default form state value.
   * @param bool $skip_on_empty
   *   Skip if the value is empty.
   *
   * @return mixed
   *   The form state value; otherwise default value.
   */
  protected function getFormStateValue(
    $key,
    FormStateInterface $form_state,
    $default_value = NULL,
    $skip_on_empty = FALSE
  ) {
    $key = !is_array($key) ? [$key] : $key;

    $inputs = [
      $form_state->getValues(),
      $form_state->getUserInput(),
    ];

    foreach ($inputs as $input) {
      $key_exists = FALSE;

      $value = NestedArray::getValue($input, $key, $key_exists);

      if ($key_exists) {
        if ($skip_on_empty && empty($value)) {
          continue;
        }
        return $value;
      }
    }

    return $default_value;
  }

}
