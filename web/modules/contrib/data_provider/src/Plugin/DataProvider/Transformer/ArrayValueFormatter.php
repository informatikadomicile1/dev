<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin\DataProvider\Transformer;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\data_provider\FormStateUtilityTrait;
use Drupal\data_provider\Contracts\DataProviderTransformerDataInterface;

/**
 * Define the array value formatter transformer.
 *
 * @DataProviderTransformer(
 *   id = "array_value_formatter",
 *   label = @Translation("Array Value Formatter"),
 *   support_multiple = TRUE
 * )
 */
class ArrayValueFormatter extends DataProviderTransformerBase {

  use FormStateUtilityTrait;

  /**
   * {@inheritDoc}
   */
  public function isApplicable(DataProviderTransformerDataInterface $data): bool {
    return is_array($data->getValue());
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'notation' => NULL,
      'formatter' => NULL,
      'settings' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $parents = $form['#parents'];
    $wrapper_parent_ids = implode('-', $parents);

    $wrapper_id = "{$wrapper_parent_ids}-json-property-formatter";

    $form['#prefix'] = "<div id='{$wrapper_id}'>";
    $form['#suffix'] = '</div>';

    $configuration = $this->getConfiguration();

    $form['notation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notation'),
      '#required' => TRUE,
      '#description' => $this->t(
        'Input a dot notation to the value you want to format in the array. <br/>
        <strong>Note</strong>: The dot notation is case sensitive.'
      ),
      '#default_value' => $configuration['notation'],
    ];

    $formatter = $this->getFormStateValue(
      ['formatter'],
      $form_state,
      $configuration['formatter']
    );

    $form['formatter'] = [
      '#type' => 'select',
      '#title' => $this->t('Formatter'),
      '#required' => TRUE,
      '#description' => $this->t(
        'Select the formatter to use to process the extracted value.'
      ),
      '#options' => $this->getFormatterOptions(),
      '#default_value' => $formatter,
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => $wrapper_id,
        'callback' => [$this, 'formAjaxCallback'],
      ],
    ];
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Formatter Settings'),
      '#tree' => TRUE,
    ];

    if (isset($formatter) && !empty($formatter)) {
      $formatter_form = [];
      $form['settings'] += $this->invokeFormatterSettingsForm(
        $formatter,
        $formatter_form,
        $form_state
      );
    }

    if (count(Element::children($form['settings'])) === 0) {
      unset($form['settings']);
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    if ($formatter = $form_state->getValue(['formatter'])) {
      $settings = $form_state->getValue(['settings'], []);

      switch ($formatter) {
        case 'callback':
          if (
            isset($settings['callback'])
            && !function_exists($settings['callback'])
          ) {
            $form_state->setError(
              $form['settings']['callback'],
              $this->t('The callback @callback does not exist.', [
                '@callback' => $settings['callback'],
              ])
            );
          }
          break;
      }
    }
  }

  /**
   * The form ajax callback.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return array
   *   An array of form elements.
   */
  public function formAjaxCallback(
    array $form,
    FormStateInterface $form_state
  ): array {
    $element = $form_state->getTriggeringElement();

    return NestedArray::getValue(
      $form,
      array_splice($element['#array_parents'], 0, -1)
    );
  }

  /**
   * {@inheritDoc}
   */
  public function transform(
    DataProviderTransformerDataInterface $data
  ): array {
    $value = $data->getValue();
    $configuration = $this->getConfiguration();

    if ($notation = $configuration['notation']) {
      try {
        $formatter = $this->getCurrentFormatterInfo();

        if (($process = $formatter['process'] ?? NULL) && is_callable($process)) {
          $parents = explode('.', $notation);

          if ($nested_value = NestedArray::getValue($value, $parents)) {
            $update_value = $process($nested_value, $configuration['settings']);

            if ($update_value !== $nested_value) {
              NestedArray::setValue(
                $value,
                $parents,
                $update_value
              );
            }
          }
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('data_provider', $exception);
      }
    }

    return $value;
  }

  /**
   * Define the JSON value formatters.
   */
  protected function formatterInfo(): array {
    $formatter = [];

    $formatter['trim'] = [
      'label' => $this->t('Trim'),
      'process' => function (string $value) {
        return trim($value);
      },
    ];
    $formatter['strtolower'] = [
      'label' => $this->t('String to Lower'),
      'process' => function (string $value) {
        return strtolower($value);
      },
    ];
    $formatter['strtoupper'] = [
      'label' => $this->t('String to Upper'),
      'process' => function (string $value) {
        return strtoupper($value);
      },
    ];
    $formatter['regex_replace'] = [
      'label' => $this->t('Regex Replace'),
      'form' => function (array $form, array $settings) {
        $form['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern'),
          '#required' => TRUE,
          '#description' => $this->t('Input the regular expression pattern.'),
          '#default_value' => $settings['pattern'],
        ];
        $form['replacement'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Replacement'),
          '#required' => TRUE,
          '#description' => $this->t('Input the regular expression replacement.'),
          '#default_value' => $settings['replacement'],
        ];
        return $form;
      },
      'process' => function (string $value, array $settings) {
        if (isset($settings['pattern']) && $settings['replacement']) {
          return preg_replace(
            "/{$settings['pattern']}/",
            $settings['replacement'],
            $value
          );
        }

        return $value;
      },
    ];
    $formatter['callback'] = [
      'label' => $this->t('Callback'),
      'form' => function (array $form, array $settings) {
        $form['callback'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Callback'),
          '#required' => TRUE,
          '#default_value' => $settings['callback'] ?? NULL,
        ];

        return $form;
      },
      'process' => function (string $value, array $settings) {
        if (($callback = $settings['callback']) && is_callable($callback)) {
          return $callback($value);
        }

        return $value;
      },
    ];

    return $formatter;
  }

  /**
   * Invoke the formatter settings form.
   *
   * @param string $name
   *   The formatter name.
   * @param array $form
   *   An array of the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return array
   *   A render array of the formatter settings form.
   */
  protected function invokeFormatterSettingsForm(
    string $name,
    array $form,
    FormStateInterface $form_state
  ): array {
    $formatter = $this->getFormatterInfo($name);
    $configuration = $this->getConfiguration();

    if (($callable = $formatter['form'] ?? NULL) && is_callable($callable)) {
      $form += $callable(
        $form,
        $form_state->getValue(['settings'], $configuration['settings'] ?? [])
      );
    }

    return $form;
  }

  /**
   * Get the current formatter info definition.
   *
   * @return array
   *   An array of the formatter info definition.
   */
  public function getCurrentFormatterInfo(): array {
    return $this->getFormatterInfo(
      $this->getConfiguration()['formatter']
    );
  }

  /**
   * Get the JSON value formatter options.
   *
   * @return array
   *   An array of the formatter options.
   */
  protected function getFormatterOptions(): array {
    $options = [];

    foreach ($this->formatterInfo() as $name => $info) {
      if (!isset($info['label'])) {
        continue;
      }
      $options[$name] = $info['label'];
    }
    ksort($options);

    return $options;
  }

  /**
   * Get the formatter information.
   *
   * @param string $name
   *   The name of the formatter.
   *
   * @return array
   *   An array of the formatter definition.
   */
  protected function getFormatterInfo(string $name): array {
    $formatter_info = $this->formatterInfo();

    if (!isset($formatter_info[$name])) {
      throw new \RuntimeException(
        sprintf('The %s formatter is invalid!', $name)
      );
    }

    return $formatter_info[$name];
  }

}
