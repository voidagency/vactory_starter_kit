<?php

namespace Drupal\vactory_webform_typesafe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_webform_typesafe\Service\ApiKeyStore;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures direct Jev authentication and submission processing defaults.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The encrypted API key store.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\ApiKeyStore
   */
  protected $keys;

  /**
   * The direct Jev client.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\TypeSafeClassifier
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->keys = $container->get('vactory_webform_typesafe.api_key');
    $instance->client = $container->get('vactory_webform_typesafe.classifier');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_webform_typesafe_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_webform_typesafe.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_webform_typesafe.settings');
    $source = $this->keys->source();
    $external = in_array($source, ['settings.php', 'environment'], TRUE);
    $form['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Jev API connection'),
    ];
    $form['connection']['status'] = [
      '#plain_text' => $source === 'none' ? $this->t('No API key configured.') : $this->t('API key source: @source. Use Save and test connection to verify access.', ['@source' => $source]),
    ];
    $form['connection']['api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Jev API key'),
      '#description' => $this->t('Leave blank to keep the current key. Stored encrypted outside configuration exports. A TYPESAFE_API_KEY environment variable or settings.php override takes precedence.'),
      '#maxlength' => 4096,
      '#disabled' => $external,
      '#attributes' => ['autocomplete' => 'new-password'],
    ];
    $form['connection']['remove_api_key'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove the stored API key'),
      '#description' => $this->t('Does not remove environment or settings.php overrides.'),
    ];
    $form['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('API timeout in seconds'),
      '#min' => 1,
      '#max' => 180,
      '#required' => TRUE,
      '#default_value' => $config->get('request_timeout') ?? 60,
    ];
    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default model'),
      '#required' => TRUE,
      '#default_value' => $config->get('model'),
    ];
    $form['max_attempts'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum attempts per analysis'),
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('max_attempts'),
    ];
    $form['max_input_bytes'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum input size in bytes'),
      '#description' => $this->t('Oversized inputs are rejected, never silently truncated.'),
      '#min' => 100,
      '#max' => 1000000,
      '#required' => TRUE,
      '#default_value' => $config->get('max_input_bytes'),
    ];
    $form = parent::buildForm($form, $form_state);
    $form['actions']['test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and test connection'),
      '#submit' => ['::submitForm', '::testConnection'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      if ($form_state->getValue('remove_api_key')) {
        $this->keys->delete();
      }
      elseif (trim((string) $form_state->getValue('api_key')) !== '' && !in_array($this->keys->source(), ['settings.php', 'environment'], TRUE)) {
        $this->keys->save($form_state->getValue('api_key'));
      }
    }
    catch (JevApiException $e) {
      $form_state->set('key_save_failed', TRUE);
      $this->messenger()->addError($e->getMessage());
      return;
    }
    finally {
      // Remove the submitted secret before any form-state caching or rebuild.
      $form_state->unsetValue('api_key');
      $input = $form_state->getUserInput();
      unset($input['api_key']);
      $form_state->setUserInput($input);
    }
    $this->config('vactory_webform_typesafe.settings')
      ->set('model', trim($form_state->getValue('model')))
      ->set('request_timeout', (int) $form_state->getValue('request_timeout'))
      ->set('max_attempts', (int) $form_state->getValue('max_attempts'))
      ->set('max_input_bytes', (int) $form_state->getValue('max_input_bytes'))->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (trim((string) $form_state->getValue('model')) === '') {
      $form_state->setErrorByName('model', $this->t('Enter a model name.'));
    }
    $key = $form_state->getValue('api_key');
    if (is_string($key) && $key !== '') {
      if ($form_state->getValue('remove_api_key')) {
        $form_state->setErrorByName('api_key', $this->t('Choose either replacing or removing the key.'));
      }
      try {
        ApiKeyStore::normalize($key);
      }
      catch (JevApiException $e) {
        $form_state->setErrorByName('api_key', $e->getMessage());
      }
    }
  }

  /**
   * Lists models to verify credentials without sending submission data.
   */
  public function testConnection(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('key_save_failed')) {
      return;
    }
    try {
      $models = $this->client->getModels();
      $this->messenger()->addStatus($this->t('Connected to Jev. Available model aliases: @models.', ['@models' => implode(', ', $models)]));
    }
    catch (JevApiException $e) {
      $this->messenger()->addError($e->getMessage());
    }
  }

}
