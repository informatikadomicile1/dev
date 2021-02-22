<?php

namespace Drupal\wsdata\Plugin\WSConnector;

use Drupal\wsdata\Plugin\WSConnectorBase;
use Drupal\wsdata\Plugin\WSConnector\WSConnectorSimpleHTTP;
use SoapClient;

/**
 * REST Connector.
 *
 * @WSConnector(
 *   id = "WSConnectorSOAP",
 *   label = @Translation("SOAP Connector", context = "WSConnector"),
 * )
 */
class WSConnectorSOAP extends WSConnectorSimpleHTTP
{
  /**
   * {@inheritdoc}
   */
  public function getMethods()
  {
    return ['create', 'read', 'update', 'delete', 'index'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions()
  {
    return [
      'path' => null,
      'methods' => [],
      'headers' => [],
      'user' => null,
      'key' => null,
    ];
  }



  /**
   * {@inheritdoc}
   */
  public function getReplacements(array $options)
  {
    return $this->findTokens($options['path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm($options = [])
  {

    $form['path'] = [
      '#title' => $this->t('Path'),
      '#description' => $this->t('The final endpoint will be <em>Server Endpoint/Path</em>'),
      '#type' => 'textfield',
      '#maxlength' => 512,
    ];

    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Authentication'),
      '#required' => true,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Authentication'),
      '#required' => true,
    ];

    $header_count = 5;

    if (isset($options['form_state'])) {
      $input = $options['form_state']->getUserInput();
      if (isset($input['headers_count'])) {
        $header_count = $input['headers_count'] + 1;
      }
    }

    $form['headers'] = [
      '#title' => $this->t('Fixed Parameters'),
      '#type' => 'fieldset',
      '#attributes' => ['id' => 'wsconnector-headers'],
    ];

    $form['headers']['headers_count'] = [
      '#type' => 'hidden',
      '#value' => $header_count,
    ];

    for ($i = 0; $i < $header_count; $i++) {
      $form['headers'][$i]['key_' . $i] = [
        '#type' => 'textfield',
        '#title' => t('Key'),
      ];

      $form['headers'][$i]['value_' . $i] = [
        '#type' => 'textfield',
        '#title' => t('Value'),
      ];
    }

    if (isset($options['form_state'])) {
      $form['headers']['add_another'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#ajax' => [
          'callback' => '\Drupal\wsdata\Plugin\WSConnector\WSConnectorSimpleHTTP::wsconnectorHttpHeaderAjaxCallback',
          'wrapper' => 'wsconnector-headers',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $form['wsdl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WSDL'),
      '#description' => $this->t('WSDL url'),
      '#required' => true,
    ];

    $form['method'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Method'),
      '#description' => $this->t('Method name'),
      '#required' => true,
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function call($options, $method, $replacements = [], $data = null, array $tokens = [])
  {
    $token_service = \Drupal::token();

    $uri = $this->endpoint . '/' . $options['path'];

    // Perform the token replace on the headers.
    if (!empty($options['headers'])) {
      for ($i = 0; $i < count($options['headers']); $i++) {
        if (!empty($options['headers'][$i]['key_' . $i])) {
          $options['headers'][$options['headers'][$i]['key_' . $i]] = $token_service->replace($options['headers'][$i]['value_' . $i], $tokens);
        }
        unset($options['headers'][$i]['key_' . $i]);
        unset($options['headers'][$i]['value_' . $i]);
        unset($options['headers'][$i]);
      }
      if (count($replacements) == count($replacements, COUNT_RECURSIVE)) {
        $payload = array_merge($replacements, $options['headers']);
      } else { // array is multidimensional
        $payload = $replacements;
        foreach ($options['headers'] as $k => $v) {
          $payload[array_keys($replacements)[0]][$k] = $v;
        }
      }
    }

    if (isset($options['method']) && !empty($options['method'])) {
      $method = $options['method'];
    }

    if (isset($options['wsdl']) && !empty($options['wsdl'])) {
      $wsdl = DRUPAL_ROOT. $options['wsdl'];
    } else {
      $wsdl = $uri;
    }

    $result = FALSE;
    try {
      if(isset($options["user"]) && isset($options["key"])){
        $service = new \SoapClient($wsdl, array('login' => $options["user"],'password' => $options["key"]));
      } else {
        $service = new \SoapClient($wsdl);
      }

      if (isset($options['wsdl']) && !empty($options['wsdl'])
        && isset($options['path']) && !empty($options['path'])) {
        $service->__setLocation($uri);
      }

      if (!is_soap_fault($service)) {
        $result = $service->__soapCall($method, !empty($options['headers']) ? array($payload) : array($replacements));
      }
    } catch (\Throwable $th) {
      $message = $this->t('SOAP call: Could not call endpoint: :uri and method: @method', [':uri' => $uri, '@method' => $method]);
      $this->setError(1, $message);
    }

    return $result;
  }

}
