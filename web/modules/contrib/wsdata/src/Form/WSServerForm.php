<?php

namespace Drupal\wsdata\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\wsdata\Plugin\WSConnectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WSServerForm.
 *
 * @package Drupal\wsdata\Form
 */
class WSServerForm extends EntityForm {

  /**
   * Connector Manager.
   *
   * @var Drupal\wsdata\Plugin\WSConnectorManager
   */
  protected $connectorManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(WSConnectorManager $plugin_manager_wsconnector, MessengerInterface $messenger) {
    $this->connectorManager = $plugin_manager_wsconnector;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('plugin.manager.wsconnector'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wsserver_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wsserver_entity->label(),
      '#description' => $this->t("Label for the Web Service Server."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wsserver_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wsdata\Entity\WSServer::load',
      ],
      '#disabled' => !$wsserver_entity->isNew(),
    ];

    $endpoint = $wsserver_entity->getEndpoint();

    if (isset($wsserver_entity->state['endpoint'])) {
      $this->messenger->addWarning(
        $this->t('The endpoint is currently being overridden by the State API.  The configured endpoint %configured and is being replaced with %endpoint.',
        [
          '%configured' => $wsserver_entity->overrides['endpoint'],
          '%endpoint' => $wsserver_entity->getEndpoint(),
        ]
      ));
      $endpoint = $wsserver_entity->overrides['endpoint'];
    }

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#maxlength' => 1024,
      '#default_value' => $endpoint,
      '#description' => $this->t('Endpoint for this webservice entity.'),
      '#required' => TRUE,
    ];

    $connector_definitions = $this->connectorManager->getDefinitions();

    $options = [];
    foreach ($connector_definitions as $key => $connector) {
      $options[$key] = $connector['label']->render();
    }

    $form['wsconnector'] = [
      '#type' => 'select',
      '#title' => $this->t('Connector'),
      '#description' => $this->t('Methods that data is retrieved.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $wsserver_entity->wsconnector,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wsserver_entity = $this->entity;
    $status = $wsserver_entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Web Service Server.', [
          '%label' => $wsserver_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Web Service Server.', [
          '%label' => $wsserver_entity->label(),
        ]));
    }
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Please confirm that `$wsserver_entity` is an instance of `Drupal\Core\Entity\EntityInterface`. Only the method name and not the class name was checked for this replacement, so this may be a false positive.
    $form_state->setRedirectUrl($wsserver_entity->toUrl('collection'));
  }

}
