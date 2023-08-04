<?php

namespace Drupal\vactory_icon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory icon setting form.
 */
class VactoryIconSettingsForm extends ConfigFormBase {

  /**
   * Vactory icon provider icon manager.
   *
   * @var \Drupal\vactory_icon\VactoryIconProviderManager
   */
  protected $iconProviderPluginManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->iconProviderPluginManager = $container->get('plugin.manager.vactory_icon');
    return $instance;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_icon.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_icon_admin_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $providers = [];
    $config = $this->config('vactory_icon.settings');
    $definitions = $this->iconProviderPluginManager->getDefinitions();
    $is_ajax = \Drupal::request()->isXmlHttpRequest();
    foreach ($definitions as $plugin_id => $definition) {
      if (method_exists($definition['class'], 'settingsForm')) {
        $icon_provider = $this->iconProviderPluginManager->createInstance($plugin_id);
        $providers[$plugin_id] = $icon_provider->description();
      }
    }
    $form += [
      '#prefix' => '<div id="provider-settings-form-wrapper">',
      '#suffix' => '</div>',
    ];

    $provider_plugin = $config->get('provider_plugin') ?? '';
    $form['provider_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon providers'),
      '#options' => $providers,
      '#empty_option' => '- Select -',
      '#description' => $this->t('Select the desired icon provider'),
      '#attributes' => [
        'id' => 'vactory-icon-provider-select',
      ],
      '#required' => TRUE,
      '#default_value' => $provider_plugin,
    ];
    $form['provider_plugin_submit'] = [
      '#type' => 'submit',
      '#value' => 'Get provider settings form',
      '#attributes' => [
        'class' => ['js-hide', 'vactory-icon-provider-trigger'],
      ],
      '#submit' => [[$this, 'updateSettingsForm']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'rebuildSettingsForm'],
        'event' => 'click',
        'wrapper' => 'provider-settings-form-wrapper',
      ],
    ];
    $provider_plugin = $is_ajax ? $form_state->get('selected_provider') : $provider_plugin;
    if ($provider_plugin) {
      $form['provider_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Provider settings'),
      ];
      $icon_provider = $this->iconProviderPluginManager->createInstance($provider_plugin);
      $form['provider_settings'] += $icon_provider->settingsForm($config);
    }

    $form['#attached']['library'][] = 'vactory_icon/settings_form';

    return parent::buildForm($form, $form_state);
  }

  /**
   * Update settings form.
   */
  public function updateSettingsForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $provider_plugin = $values['provider_plugin'] ?? NULL;
    $form_state->set('selected_provider', $provider_plugin);
    $form_state->setRebuild();
  }

  /**
   * Rebuild settings form.
   */
  public function rebuildSettingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $provider_plugin = $values['provider_plugin'] ?? NULL;
    $config = $this->config('vactory_icon.settings');
    $config->set('provider_plugin', $provider_plugin)
      ->save();
    if ($provider_plugin) {
      $icon_provider = $this->iconProviderPluginManager->createInstance($provider_plugin);
      $icon_provider->settingsFormSubmit($form_state, $config);
    }
  }

}
