<?php

namespace Drupal\js_component\Plugin\Block;

use Drupal\block\BlockForm;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\js_component\JSComponentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define JS component block.
 *
 * @Block(
 *   id = "js_component",
 *   category = @Translation("JS Component"),
 *   admin_label = @Translation("JS Component"),
 *   deriver = "\Drupal\js_component\Plugin\Deriver\JSComponentsBlocksDeriver"
 * )
 */
class JSComponentBlockType extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var string
   */
  protected $componentRootId;

  /**
   * @var LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * @var ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * @var JSComponentManagerInterface
   */
  protected $jsComponentManager;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'overrides' => [],
        'js_component' => [],
        'component_data' => [],
      ] + parent::defaultConfiguration();
  }

  /**
   * JS component block constructor.
   *
   * @param array $configuration
   *   The plugin configurations.
   * @param $plugin_id
   *   The plugin identifier.
   * @param $plugin_definition
   *   The plugin metadata definition.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param ElementInfoManagerInterface $element_info_manager
   *   The element information manager service.
   * @param \Drupal\js_component\JSComponentManagerInterface $js_component_manager
   *   The JS component manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LibraryDiscoveryInterface $library_discovery,
    ElementInfoManagerInterface $element_info_manager,
    JSComponentManagerInterface $js_component_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->libraryDiscovery = $library_discovery;
    $this->elementInfoManager = $element_info_manager;
    $this->jsComponentManager = $js_component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('library.discovery'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.js_component')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['#process'][] = [$this, 'processBuildComponent'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    $component = $this->getComponentInstance();

    /** @var \Drupal\js_component\JSComponentFormInterface $handler */
    if ($handler = $component->settingsClassHandler($this->getComponentSettings())) {
      $subform = $form['js_component'];
      $handler->validateComponentForm(
        $subform,
        SubformState::createForSubform(
          $subform, $form, $form_state
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = [];
    $component = $this->getComponentInstance();

    /** @var \Drupal\js_component\JSComponentFormInterface $handler */
    if ($handler = $component->settingsClassHandler($this->getComponentSettings())) {
      $subform = $this->resolveComponentSubform($form, $form_state);

      $parent_form = NestedArray::getValue(
        $form_state->getCompleteForm(),
        array_slice($subform['#array_parents'], 0, -1)
      );

      $handler->submitComponentForm(
        $subform,
        SubformState::createForSubform(
          $subform, $parent_form, $form_state
        )
      );
      $values = $handler->getConfiguration();
    }
    elseif ($component->hasSettings()) {
      $values = $form_state->getValue('js_component');
    }

    $this->configuration['js_component'] = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = $this->buildComponentTemplate();

    if ($attachments = $this->getComponentAttachments()) {
      $build['#attached']['drupalSettings']['jsComponent'] = $attachments;
    }

    if ($this->hasLibraryForComponent()) {
      $build['#attached']['library'][] = "js_component/{$this->getComponentId()}";
    }

    return $build;
  }

  /**
   * Get block components classes.
   *
   * @return array
   *   An array of the block component classes.
   */
  public function getBlockComponentClasses() {
    $classes[] = 'js-component';
    $classes[] = 'js-component--' . Html::getClass($this->getComponentPluginId());

    return $classes;
  }

  /**
   * The build component process callback.
   *
   * @param array $form
   *   An array of the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return array
   *   An array of the processed form elements.
   */
  public function processBuildComponent(array $form, FormStateInterface $form_state) {
    $form['js_component'] = [
      '#type' => 'details',
      '#title' => $this->t('JS Component'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['js_component']['#parents'] = array_merge(
      $form['#parents'], ['js_component']
    );
    $component = $this->getComponentInstance();

    /** @var \Drupal\js_component\JSComponentFormInterface $handler */
    if ($handler = $component->settingsClassHandler($this->getComponentSettings())) {
      $subform = $form['js_component'];

      $form['js_component'] = $handler->buildComponentForm(
        $subform,
        SubformState::createForSubform(
          $subform,
          $form_state->getCompleteForm(),
          $form_state
        )
      );
    }
    else if ($component->hasSettings()) {
      $form['js_component'] = $this->buildComponentFormElements($form);
    }

    if (count(Element::children($form['js_component'])) === 0) {
      unset($form['js_component']);
    }

    return $form;
  }

  /**
   * Resolve the component subform array.
   *
   * @param array $form
   *   The form elements to assess.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return array
   *   The component subform array.
   */
  protected function resolveComponentSubform(
    array $form,
    FormStateInterface $form_state
  ) {
    $object = $form_state->getFormObject();
    $parents = ['js_component'];

    // Since the block form provides the original form array instead of the
    // subform array; it's not possible to determine the parents array.
    if ($object instanceof BlockForm) {
      $parents = ['settings', 'js_component'];
    }

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Build component template.
   *
   * @return array
   *   Am render array of the component template.
   */
  protected function buildComponentTemplate() {
    $base = ['#block' => $this];

    /** @var \Drupal\js_component\Plugin\JSComponent $component */
    $component = $this->getComponentInstance();

    if ($component->hasTemplate()) {
      return [
        '#theme' => $this->getComponentId(),
        '#settings' => $this->getComponentSettings(TRUE),
      ] + $base;
    }

    return [
      '#type' => 'inline_template',
      '#template' => '<div id="{{ root_id }}" class="{{ classes }}"></div>',
      '#context' => [
        'root_id' => $this->getComponentRootId(),
        'classes' => implode(' ', $this->getBlockComponentClasses())
      ],
    ] + $base;
  }

  /**
   * JS component identifier.
   *
   * @return mixed
   */
  protected function getComponentId() {
    return $this->pluginDefinition['component_id'];
  }

  /**
   * Get component root identifier.
   *
   * @return string
   *   The component root identifier.
   */
  protected function getComponentRootId() {
    if (!isset($this->componentRootId)) {
      $prefix = 'settings:';
      $root_id = $this->getComponentInstance()->rootId();

      if (strpos($root_id, $prefix) !== FALSE) {
        $name = substr($root_id, strlen($prefix));
        $settings = $this->getComponentSettings();

        if (isset($settings[$name])) {
          $root_id = $settings[$name];
        }
      }

      $this->componentRootId = Html::getUniqueId(
        $root_id
      );
    }

    return $this->componentRootId;
  }

  /**
   * JS component instance.
   *
   * @return \Drupal\js_component\Plugin\JSComponent
   */
  protected function getComponentInstance() {
    return $this->jsComponentManager
      ->createInstance($this->getComponentPluginId(), [
        'overrides' => $this->getConfigurationOverrides()
      ]);
  }

  /**
   * Get the component attachments.
   *
   * @return array
   *   An array of component attachments.
   */
  protected function getComponentAttachments() {
    $attachments = [];

    $root_id = $this->getComponentRootId();
    $plugin_id = $this->getComponentPluginId();

    if ($data = $this->buildComponentData()) {
      $attachments[$plugin_id][$root_id]['data'] = $data;
    }

    if ($settings = $this->getComponentSettings(TRUE)) {
      $attachments[$plugin_id][$root_id]['settings'] = $settings;
    }

    return $attachments;
  }

  /**
   * JS component plugin identifier.
   *
   * @return string
   *   The JS component plugin identifier.
   */
  protected function getComponentPluginId() {
    $plugin_id = $this->getPluginId();
    return substr($plugin_id, strpos($plugin_id, ':') + 1);
  }

  /**
   * Build the component data.
   *
   * @return array
   *   An array of the component data.
   */
  protected function buildComponentData() {
    $data = $this->getComponentData();

    if (!empty($data)) {
      return $data;
    }
    $provider = $this->getComponentDataProvider();

    if ($provider === FALSE) {
      return [];
    }

    return $provider->fetch();
  }

  /**
   * Get the component data provider instance.
   *
   * @return bool|\Drupal\js_component\JsComponentDataProviderInterface
   *   Return the component data provider; otherwise FALSE if it doesn't exist.
   */
  protected function getComponentDataProvider() {
    $component = $this->getComponentInstance();

    return $component->dataProviderClassHandler(
      $this->getComponentSettings()
    );
  }

  /**
   * JS component has libraries defined.
   *
   * @return bool
   *   Determine if the JS component has a library defined.
   */
  protected function hasLibraryForComponent() {
    $status = $this
      ->libraryDiscovery
      ->getLibraryByName('js_component', "{$this->getComponentId()}");

    return $status !== FALSE ? TRUE : FALSE;
  }

  /**
   * Recursive clean values.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   An array of cleaned values.
   */
  protected function recursiveCleanValues(array $values) {
    foreach ($values as $key => &$value) {
      if (is_array($value)) {
        $value = $this->recursiveCleanValues($value);
      }
    }

    return array_filter($values);
  }

  /**
   * Build the component form elements.
   *
   * @param $form
   *   An array of form elements.
   *
   * @return array
   *   The component form elements.
   */
  protected function buildComponentFormElements($form) {
    /** @var \Drupal\js_component\Plugin\JSComponent $component */
    $component = $this->getComponentInstance();
    $settings = $this->getComponentSettings();

    foreach ($component->settings() as $field_name => $field_info) {
      if (!isset($field_info['type'])
        || !$this->elementIsValid($field_info['type'])) {
        continue;
      }
      $element = $this->formatFormElement($field_info);

      if (isset($settings[$field_name])
        && !empty($settings[$field_name])) {
        $element['#default_value'] = $settings[$field_name];
      }

      $form[$field_name] = $element;
    }

    return $form;
  }

  /**
   * Format form element.
   *
   * @param array $element_info
   *   An array of the element key and value.
   *
   * @return array
   *   The formatted form element.
   */
  protected function formatFormElement(array $element_info) {
    $element = [];

    foreach ($element_info as $key => $value) {
      if (empty($value)) {
        continue;
      }
      $element["#{$key}"] = $value;
    }

    return $element;
  }

  /**
   * Form element is valid.
   *
   * @param $type
   *   The type of form element.
   *
   * @return bool
   *   Return TRUE if the element type is valid; otherwise FALSE.
   */
  protected function elementIsValid($type) {
    if (!$this->elementInfoManager->hasDefinition($type)) {
      return FALSE;
    }
    $element_type = $this
      ->elementInfoManager
      ->createInstance($type);

    return $element_type instanceof FormElementInterface;
  }

  /**
   * Get configuration overrides.
   *
   * @return array
   */
  protected function getConfigurationOverrides() {
    return $this->getConfiguration()['overrides'];
  }

  /**
   * Get the component data.
   *
   * @return array
   *   An array of component data object.
   */
  protected function getComponentData() {
    return $this->getConfiguration()['component_data'];
  }

  /**
   * Get the component settings.
   *
   * @param bool $clean
   *   If TRUE the component configuration values are cleaned; default to FALSE.
   *
   * @return array
   *   An array of component settings.
   */
  protected function getComponentSettings($clean = FALSE) {
    $settings = $this->getConfiguration()['js_component'] ;

    if (!$clean) {
      return $settings;
    }

    return $this->recursiveCleanValues($settings);
  }
}
