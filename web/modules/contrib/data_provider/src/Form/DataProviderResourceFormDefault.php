<?php

declare(strict_types=1);

namespace Drupal\data_provider\Form;

use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\data_provider\FormStateUtilityTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface;

/**
 * Define the data provider resource default form.
 */
class DataProviderResourceFormDefault extends EntityForm {

  use FormStateUtilityTrait;

  /**
   * Fetcher plugin manager.
   *
   * @var \Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface
   */
  protected $fetcherPluginManager;

  /**
   * Transformer plugin manager.
   *
   * @var \Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface
   */
  protected $transformerPluginManager;

  /**
   * The class constructor.
   *
   * @param \Drupal\data_provider\Contracts\DataProviderFetcherManagerInterface $fetcher_plugin_manager
   *   The fetch plugin manager service.
   * @param \Drupal\data_provider\Contracts\DataProviderTransformerManagerInterface $transformer_plugin_manager
   *   The transformer plugin manager service.
   */
  public function __construct(
    DataProviderFetcherManagerInterface $fetcher_plugin_manager,
    DataProviderTransformerManagerInterface $transformer_plugin_manager
  ) {
    $this->fetcherPluginManager = $fetcher_plugin_manager;
    $this->transformerPluginManager = $transformer_plugin_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.data_provider.fetcher'),
      $container->get('plugin.manager.data_provider.transformer')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#parents'] = [];
    $form['#prefix'] = '<div id="data-provider-resource-form">';
    $form['#suffix'] = '</div>';

    /** @var \Drupal\data_provider\Entity\DataProviderResource $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource Label'),
      '#description' => $this->t(
        'Input the data provider resource label.'
      ),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['fetcher'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Resource Fetcher'),
      '#tree' => TRUE,
    ];

    $fetcher_plugin_id = $this->getFormStateValue(
      ['fetcher', 'plugin_id'],
      $form_state,
      $entity->fetcherPluginId()
    );

    $form['fetcher']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Fetcher Plugin'),
      '#options' => $this->getFetcherOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#depth' => 2,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'data-provider-resource-form',
        'callback' => [$this, 'ajaxDepthCallback'],
      ],
      '#default_value' => $fetcher_plugin_id,
    ];

    if (isset($fetcher_plugin_id) && !empty($fetcher_plugin_id)) {
      $fetcher_manager = $this->fetcherPluginManager;

      $form['fetcher']['settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Fetcher Settings'),
        '#tree' => TRUE,
      ];

      try {
        /** @var \Drupal\data_provider\Contracts\DataProviderFetcherInterface $instance */
        $instance = $fetcher_manager->createInstance(
          $fetcher_plugin_id,
          $entity->fetcherSettings()
        );

        if ($instance instanceof PluginFormInterface) {
          $subform = ['#parents' => ['fetcher', 'settings']];

          $form['fetcher']['settings'] += $instance->buildConfigurationForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('data_provider', $exception);
      }

      $form['transformer'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Resource Transformer'),
        '#tree' => TRUE,
        '#prefix' => '<div id="transformer">',
        '#suffix' => '</div>',
      ];

      $form['transformer']['plugins'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Plugin ID'),
          $this->t('Settings'),
          $this->t('Operations'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
        '#empty' => $this->t('There are no transformer plugins!'),
      ];

      $transformer_count = $this->formStateStorageItemCount(
        ['data_provider', 'transformer_count'],
        $entity->transformerPluginsCount(),
        $form_state
      );

      $transformer_plugins = $this->getFormStateValue(
        ['transformer', 'plugins'],
        $form_state,
        $entity->transformerPlugins(),
        TRUE
      );
      $transformer_options = $this->getTransformerOptions();

      for ($i = 0; $i < $transformer_count; $i++) {
        $transformer_plugin = $transformer_plugins[$i] ?? [];
        $transformer_id = $transformer_plugin['plugin_id'] ?? NULL;

        $form['transformer']['plugins'][$i]['#attributes']['class'][] = 'draggable';

        $form['transformer']['plugins'][$i]['plugin_id'] = [
          '#type' => 'select',
          '#options' => $transformer_options,
          '#empty_option' => $this->t('- None -'),
          '#default_value' => $transformer_id,
          '#required' => TRUE,
          '#depth' => 3,
          '#ajax' => [
            'event' => 'change',
            'method' => 'replace',
            'wrapper' => 'transformer',
            'callback' => [$this, 'ajaxDepthCallback'],
          ],
        ];
        $form['transformer']['plugins'][$i]['settings'] = [
          '#type' => 'container',
          '#tree' => TRUE,
        ];

        if (isset($transformer_id) && !empty($transformer_id)) {
          try {
            $instance = $this->transformerPluginManager->createInstance(
              $transformer_id,
              $transformer_plugin['settings'] ?? []
            );

            if ($instance instanceof PluginFormInterface) {
              $subform = [
                '#parents' => ['transformer', 'plugins', $i, 'settings'],
              ];
              $form['transformer']['plugins'][$i]['settings'] += $instance->buildConfigurationForm(
                $subform,
                SubformState::createForSubform($subform, $form, $form_state)
              );
            }
          }
          catch (\Exception $exception) {
            watchdog_exception('data_provider', $exception);
          }
        }
        $settings_children = Element::children(
          $form['transformer']['plugins'][$i]['settings']
        );

        if (count($settings_children) === 0) {
          $form['transformer']['plugins'][$i]['settings']['message'] = [
            '#markup' => $this->t(
              'There are no settings for the @label plugin.', [
                '@label' => $transformer_options[$transformer_id],
              ]
            ),
          ];
        }

        $form['transformer']['plugins'][$i]['operations'] = [
          '#op' => 'remove',
          '#type' => 'submit',
          '#depth' => 3,
          '#delta' => $i,
          '#value' => $this->t('Remove'),
          '#submit' => [
            [$this, 'ajaxOpActionSubmit'],
          ],
          '#ajax' => [
            'method' => 'replace',
            'event' => 'click',
            'wrapper' => 'transformer',
            'callback' => [$this, 'ajaxDepthCallback'],
          ],
          '#limit_validation_errors' => [],
          '#name' => "transformer-plugin-{$i}-operation",
        ];

        $form['transformer']['plugins'][$i]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => 0,
          '#attributes' => [
            'class' => [
              'table-sort-weight',
            ],
          ],
        ];
      }
      $form['transformer']['action']['#type'] = 'action';
      $form['transformer']['action']['add_more'] = [
        '#op' => 'add',
        '#depth' => 2,
        '#type' => 'submit',
        '#value' => $this->t('Add Transformer'),
        '#submit' => [
          [$this, 'ajaxOpActionSubmit'],
        ],
        '#ajax' => [
          'method' => 'replace',
          'event' => 'click',
          'wrapper' => 'transformer',
          'callback' => [$this, 'ajaxDepthCallback'],
        ],
        '#limit_validation_errors' => [['transformer']],
      ];

      $form['caching'] = [
        '#type' => 'details',
        '#title' => $this->t('Resource Caching'),
        '#open' => FALSE,
        '#tree' => TRUE,
      ];
      $form['caching']['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#description' => $this->t('Enable resource caching.'),
        '#default_value' => $entity->cachingEnabled(),
      ];
      $form['caching']['expired'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Expired'),
        '#description' => $this->t('Input the cache expiration. Use textual
          datetime description such as +1 day, +2 weeks, tomorrow, etc.<br/>
          <strong>Note</strong>: If left empty than the resource will be
          permanently cached, unless invalidated elsewhere.'),
        '#default_value' => $entity->cachingEnabled(),
      ];
      $form['caching']['tags'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Caching Tags'),
        '#description' => $this->t('Input cache tags that should invalidate the
          resources transformed data. <br/> <strong>Note:</strong> Add a cache
          tag one per line in the following format [entity-type]:[entity-id].'),
        '#default_value' => implode("\r\n", $entity->resourceCachingTags()),
      ];
      $form['caching']['recursive'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Recursive'),
        '#description' => $this->t('If checked, caching tags will be recursively
          added based on referenced entities.'),
        '#default_value' => $entity->cachingRecursive(),
      ];
      $form['caching']['exclude'] = [
        '#type' => 'details',
        '#title' => $this->t('Exclude'),
        '#open' => FALSE,
      ];
      $form['caching']['exclude']['entity_types'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Types'),
        '#description' => $this->t('Select the entity types to exclude resource
          caching for. <br/> <strong>NOTE:</strong> If nothing is selected then
          all entity types are allowed.'),
        '#options' => $this->getEntityTypeOptions(),
        '#multiple' => TRUE,
        '#default_value' => $entity->cachingExcludeEntityTypes(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::validateForm($form, $form_state);

    if ($plugin_id = $form_state->getValue(['fetcher', 'plugin_id'])) {
      $fetcher_manager = $this->fetcherPluginManager;
      $plugin_base_parents = ['fetcher', 'settings'];

      try {
        $instance = $fetcher_manager->createInstance(
          $plugin_id,
          $form_state->getValue($plugin_base_parents, [])
        );

        if ($instance instanceof PluginFormInterface) {
          $subform = NestedArray::getValue($form, $plugin_base_parents) ?? [];

          $instance->validateConfigurationForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('data_provider', $exception);
      }
    }

    if ($plugins = $form_state->getValue(['transformer', 'plugins'])) {
      $plugin_used = [];
      $plugin_base_parents = ['transformer', 'plugins'];

      foreach ($plugins as $index => $transformer) {
        if (!isset($transformer['plugin_id'])) {
          continue;
        }
        $plugin_id = $transformer['plugin_id'];
        $transformer_manager = $this->transformerPluginManager;
        $index_parents = array_merge($plugin_base_parents, [$index]);

        try {
          /** @var \Drupal\data_provider\Contracts\DataProviderTransformerInterface $instance */
          $instance = $transformer_manager->createInstance(
            $plugin_id,
            $transformer['settings'] ?? []
          );
          $definition = $transformer_manager->getDefinition($plugin_id);
          $support_multiple = $definition['support_multiple'] ?? FALSE;

          if (!$support_multiple && in_array($plugin_id, $plugin_used, TRUE)) {
            $form_state->setError(
              NestedArray::getValue($form, array_merge($index_parents, ['plugin_id'])),
              $this->t("The @label plugin can't be declared multiple times.", [
                '@label' => $definition['label'],
              ])
            );
          }
          $plugin_used[] = $plugin_id;

          if ($instance instanceof PluginFormInterface) {
            $settings_parents = array_merge($index_parents, ['settings']);
            $subform = NestedArray::getValue($form, $settings_parents) ?? [];

            $instance->validateConfigurationForm(
              $subform,
              SubformState::createForSubform($subform, $form, $form_state)
            );
          }
        }
        catch (\Exception $exception) {
          watchdog_exception('data_provider', $exception);
        }
      }
      uasort($plugins, [SortArray::class, 'sortByWeightElement']);

      $form_state->setValue($plugin_base_parents, array_values($plugins));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    if ($plugin_id = $form_state->getValue(['fetcher', 'plugin_id'])) {
      $fetcher_manager = $this->fetcherPluginManager;
      $plugin_base_parents = ['fetcher', 'settings'];

      try {
        /** @var \Drupal\data_provider\Contracts\DataProviderFetcherInterface $instance */
        $instance = $fetcher_manager->createInstance($plugin_id);

        if ($instance instanceof PluginFormInterface) {
          $subform = NestedArray::getValue($form, $plugin_base_parents) ?? [];

          $instance->submitConfigurationForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );

          $form_state->setValue($plugin_base_parents, $instance->getConfiguration());
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('data_provider', $exception);
      }
    }

    if ($plugins = $form_state->getValue(['transformer', 'plugins'])) {
      $transformer_manager = $this->transformerPluginManager;
      $plugin_base_parents = ['transformer', 'plugins'];

      foreach ($plugins as $index => $transformer) {
        if (!isset($transformer['plugin_id'])) {
          continue;
        }
        $index_parents = array_merge($plugin_base_parents, [$index]);

        try {
          /** @var \Drupal\data_provider\Contracts\DataProviderTransformerInterface $instance */
          $instance = $transformer_manager->createInstance(
            $transformer['plugin_id'],
            $transformer['settings'] ?? []
          );
          $setting_parents = array_merge($index_parents, ['settings']);

          if ($instance instanceof PluginFormInterface) {
            $subform = NestedArray::getValue($form, $setting_parents) ?? [];

            $instance->submitConfigurationForm(
              $subform,
              SubformState::createForSubform($subform, $form, $form_state)
            );
            $settings = $instance->getConfiguration();

            $form_state->setValue($setting_parents, $settings);
          }
        }
        catch (\Exception $exception) {
          watchdog_exception('data_provider', $exception);
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $form_state->setRedirectUrl(
      $this->entity->toUrl('collection')
    );

    $this->messenger()->addMessage($this->t(
      'The data provider resource %label has been @action!', [
        '%label' => $this->entity->label(),
        '@action' => $status === SAVED_NEW ? 'saved' : 'updated',
      ]
    ));

    return $status;
  }

  /**
   * Ajax operation action trigger submit.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  public function ajaxOpActionSubmit(
    array $form,
    FormStateInterface $form_state
  ): void {
    $button = $form_state->getTriggeringElement();

    if (isset($button['#op'])) {
      $count = $form_state->get(['data_provider', 'transformer_count']) ?? 0;

      switch ($button['#op']) {
        case 'add':
          $count++;
          break;

        case 'remove':
          $this->reindexFormStateValue(
            ['transformer', 'plugins'],
            $button['#delta'],
            $form_state
          );

          if ($count !== 0) {
            $count--;
          }

          break;
      }

      $form_state->set('data_provider', [
        'transformer_count' => $count,
      ]);
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax depth callback.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The forms state instance.
   *
   * @return array
   *   Return the form based on the #depth value, if omitted then full form
   *   is returned.
   */
  public function ajaxDepthCallback(
    array $form,
    FormStateInterface $form_state
  ): array {
    $button = $form_state->getTriggeringElement();

    if (!isset($button['#depth'])) {
      return $form;
    }

    return (array) NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -$button['#depth'])
    );
  }

  /**
   * Determine if the data provider resource exist.
   *
   * @param string $id
   *   The entity identifier.
   *
   * @return bool
   *   Return TRUE if the data provider resource exist; otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function exists(string $id): bool {
    return (bool) $this->getEntityStorage()->getQuery()
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Get entity type options.
   *
   * @return array
   *   An array of entity type options.
   */
  protected function getEntityTypeOptions(): array {
    $options = [];
    $definitions = $this->entityTypeManager->getDefinitions();

    foreach ($definitions as $entity_type_id => $definition) {
      if (!$definition instanceof ContentEntityTypeInterface) {
        continue;
      }
      $options[$entity_type_id] = $definition->getLabel();
    }
    ksort($options);

    return $options;
  }

  /**
   * Get form state storage item count.
   *
   * @param string|array $key
   *   The form state storage key.
   * @param mixed $default
   *   The form state default value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return int
   *   The storage item count value.
   */
  protected function formStateStorageItemCount(
    $key,
    $default,
    FormStateInterface $form_state
  ): int {
    if (!$form_state->has($key)) {
      $form_state->set($key, $default);
    }

    return $form_state->get($key) ?? 0;
  }

  /**
   * Reindex the form state value.
   *
   * @param string|array $key
   *   The key value.
   * @param int $delta
   *   The value delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  protected function reindexFormStateValue(
    $key,
    int $delta,
    FormStateInterface $form_state
  ): void {
    if (!is_array($key)) {
      $key = [$key];
    }
    foreach (['getValues', 'getUserInput'] as $method) {
      $values = NestedArray::getValue($form_state->$method(), $key) ?? [];
      unset($values[$delta]);
      NestedArray::setValue($form_state->$method(), $key, array_values($values));
    }
  }

  /**
   * Get the data provider transformer options.
   *
   * @return array
   *   An array of transformer options.
   */
  protected function getTransformerOptions(): array {
    $options = $this->transformerPluginManager->getOptions();
    asort($options);
    return $options;
  }

  /**
   * Get the data provider fetcher options.
   *
   * @return array
   *   An array of fetcher options.
   */
  protected function getFetcherOptions(): array {
    return $this->fetcherPluginManager->getOptions();
  }

  /**
   * Get entity storage instance.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('data_provider_resource');
  }

}
