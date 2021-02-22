<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin\DataProvider\Fetcher;

use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\data_provider\DataProviderFetcherResponse;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the HTTP request fetcher plugin.
 *
 * @DataProviderFetcher(
 *   id = "http_request",
 *   label = @Translation("HTTP Request")
 * )
 */
class HttpRequestFetcher extends DataProviderFetcherBase implements ContainerFactoryPluginInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The HTTP request fetcher constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client instance.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    ClientInterface $http_client
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'url' => NULL,
      'type' => 'external',
      'request_options' => [
        'verify' => TRUE,
        'timeout' => 0,
        'read_timeout' => ini_get('default_socket_timeout'),
        'connect_timeout' => 0,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('URL Type'),
      '#options' => [
        'internal' => $this->t('Internal'),
        'external' => $this->t('External'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['type'],
    ];
    $form['url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Input a valid internal/external URL.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['url'],
    ];
    $form['request_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Request Options'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $request_options = $this->getConfiguration()['request_options'];
    $form['request_options']['verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify'),
      '#description' => $this->t('Enable SSL certificate verification.'),
      '#default_value' => $request_options['verify'],
    ];
    $form['request_options']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('Set the total timeout of the request. Use 0
        for indefinitely.'),
      '#min' => 0,
      '#required' => TRUE,
      '#field_suffix' => 'seconds',
      '#default_value' => $request_options['timeout'],
    ];
    $form['request_options']['read_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Read Timeout'),
      '#description' => $this->t('Set the timeout to use when reading a streamed body.'),
      '#min' => 0,
      '#required' => TRUE,
      '#field_suffix' => 'seconds',
      '#default_value' => $request_options['read_timeout'],
    ];
    $form['request_options']['connect_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Connection Timeout'),
      '#description' => $this->t('Set how long to wait while trying to connect.
        Use 0 for indefinitely.'),
      '#min' => 0,
      '#required' => TRUE,
      '#field_suffix' => 'seconds',
      '#default_value' => $request_options['connect_timeout'],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (($form_state->getValue('type') === 'external')
      && strpos($form_state->getValue('url'), 'http') !== 0) {
      $form_state->setError(
        $form['url'],
        $this->t('External URL needs to start with a HTTP protocol.')
      );
    }

    if (($form_state->getValue('type') === 'internal')
      && strpos($form_state->getValue('url'), '/') !== 0) {
      $form_state->setError(
        $form['url'],
        $this->t('Internal URL needs to start with a forward slash.')
      );
    }
  }

  /**
   * {@inheritDoc}
   */
  public function fetch(): DataProviderFetcherResponse {
    try {
      if ($url = $this->getConfiguration()['url']) {
        if ($this->getConfiguration()['type'] === 'internal') {
          $url = Url::fromUserInput($url, ['absolute' => TRUE])->toString();
        }
        $response = $this->httpClient->get(
          $url,
          $this->getConfiguration()['request_options'] ?? []
        );

        if ($response->getStatusCode() !== 200) {
          throw new \RuntimeException(
            sprintf('Resource failed for the HTTP request to %s.', $url)
          );
        }

        return new DataProviderFetcherResponse($response, $this->pluginId);
      }
      else {
        throw new \RuntimeException('Resource URL is required!');
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('data_provider', $exception);
    }
  }

}
