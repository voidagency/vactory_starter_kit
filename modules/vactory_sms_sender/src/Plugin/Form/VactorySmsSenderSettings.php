<?php

namespace Drupal\vactory_sms_sender\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * VActory SMS Sender Module settings.
 */
class VactorySmsSenderSettings extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_sms_sender.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_sms_sender_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('vactory_sms_sender.settings');
    $form = parent::buildForm($form, $form_state);

    // Auto configure according to given API provider.
    $form['infobip_settings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Configure according to Infobip'),
      '#name' => 'infobip',
      '#submit' => [[$this, 'autoConfigurateSubmit']],
      '#limit_validation_errors' => [],
    ];
    $form['twilio_settings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Configure according to Twilio'),
      '#name' => 'twilio',
      '#submit' => [[$this, 'autoConfigurateSubmit']],
      '#limit_validation_errors' => [],
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint'),
      '#required' => TRUE,
      '#default_value' => $config->get('endpoint'),
    ];
    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMS from'),
      '#required' => TRUE,
      '#description' => $this->t('Enter a number or string which will appears as sender of SMS'),
      '#default_value' => $config->get('from'),
    ];
    $form['authorization'] = [
      '#type' => 'select',
      '#title' => $this->t('Authorization'),
      '#options' => [
        'no_auth' => $this->t('No Auth'),
        'api_key' => $this->t('API Key'),
        'bearer' => $this->t('Bearer Token'),
        'basic' => $this->t('Basic Auth'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#description' => $this->t('Select authorization type'),
      '#default_value' => $config->get('authorization'),
    ];

    // Authorization setting wrapper.
    $form['authorization_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authorization Settings'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          '#edit-authorization' => [
            ['value' => 'api_key'],
            ['value' => 'bearer'],
            ['value' => 'basic'],
          ],
        ],
      ],
    ];
    $form['authorization_settings']['api_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key / Token'),
      '#states' => [
        'visible' => [
          '#edit-authorization' => [
            ['value' => 'api_key'],
            ['value' => 'bearer'],
          ],
        ],
      ],
      '#default_value' => $config->get('api_access_token'),
    ];
    $form['authorization_settings']['api_key_add_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Add to'),
      '#options' => [
        'header' => $this->t('Header (Default)'),
        'query' => $this->t('Query Params'),
      ],
      '#default_value' => !empty($config->get('api_key_add_to')) ? $config->get('api_key_add_to') : 'header',
      '#states' => [
        'visible' => [
          '#edit-authorization' => ['value' => 'api_key'],
        ],
      ],
    ];
    $form['authorization_settings']['api_key_query_param_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key query param name'),
      '#description' => 'The API query param name. <strong>Example</strong>: If the value is "toto" so API Key query param will be <strong>https://example.com?toto=ApiKeyHere</strong>',
      '#states' => [
        'visible' => [
          '#edit-authorization' => ['value' => 'api_key'],
          '#edit-api-key-add-to' => ['value' => 'query'],
        ],
      ],
      '#default_value' => $config->get('api_key_query_param_name'),
    ];
    $form['authorization_settings']['api_key_header_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key header prefix'),
      '#description' => 'This will prefix the API key in Authorization header section. <strong>Example</strong>: if the value is "App" so the header will have: <strong>Authorization: App ApiKeyHere</strong> ',
      '#states' => [
        'visible' => [
          '#edit-authorization' => ['value' => 'api_key'],
          '#edit-api-key-add-to' => ['value' => 'header'],
        ],
      ],
      '#default_value' => $config->get('api_key_header_prefix'),
    ];
    $form['authorization_settings']['basic_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#states' => [
        'visible' => [
          '#edit-authorization' => ['value' => 'basic'],
        ],
      ],
      '#default_value' => $config->get('basic_auth_username'),
    ];
    $form['authorization_settings']['basic_auth_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#states' => [
        'visible' => [
          '#edit-authorization' => ['value' => 'basic'],
        ],
      ],
      '#default_value' => $config->get('basic_auth_password'),
    ];

    $form['api_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type Header'),
      '#options' => [
        'json' => $this->t('JSON'),
        'form_params' => $this->t('x-www-form-urlencoded'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $config->get('api_content_type'),
      '#required' => TRUE,
    ];
    $data_keys = $config->get('data_keys');
    $data_keys_value = '';
    if (!empty($data_keys)) {
      $counter = 0;
      foreach ($data_keys as $key => $value) {
        $counter++;
        $data_keys_value .= $key . '|' . $value;
        $data_keys_value .= $counter < count($data_keys) ? PHP_EOL : '';
      }
    }
    $form['data_keys'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API Data keys mapping'),
      '#default_value' => !empty($data_keys_value) ? $data_keys_value : $this->getApiDataKeysDefaultValue(),
      '#description' => 'Enter your API SMS data keys in format sms_from_key|ApiExpectedFromKeyHere. <strong>Example</strong>: For infobip API SMS from key is "from" so enter  <strong>sms_from_key|from</strong>',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // API authorization type validation.
    $authorization_type = $form_state->getValue('authorization');
    if (in_array($authorization_type, ['api_key', 'bearer'])) {
      $api_access_token = $form_state->getValue('api_access_token');
      $api_key_add_to = $form_state->getValue('api_key_add_to');
      $api_key_query_param_name = $form_state->getValue('api_key_query_param_name');
      if (empty($api_access_token)) {
        $form_state->setErrorByName('api_access_token', $this->t('API Key / Token Field is required'));
      }
      if ($api_key_add_to === 'query' && empty($api_key_query_param_name)) {
        $form_state->setErrorByName('api_key_query_param_name', $this->t('API Key query param name field is required'));
      }
    }
    if ($authorization_type === 'basic') {
      $basic_auth_username = $form_state->getValue('basic_auth_username');
      $basic_auth_password = $form_state->getValue('basic_auth_password');
      if (empty($basic_auth_username)) {
        $form_state->setErrorByName('basic_auth_username', $this->t('Basic Auth username field is required'));
      }
      if (empty($basic_auth_password)) {
        $form_state->setErrorByName('basic_auth_password', $this->t('Basic Auth password field is required'));
      }
    }

    // API data keys validation.
    $data_keys = $form_state->getValue('data_keys');
    $data_keys = preg_split('/\r\n|\r|\n/', $data_keys);
    $final_data_keys = [];
    foreach ($data_keys as $data_key) {
      $data_key = explode('|', $data_key);
      $final_data_keys[$data_key[0]] = $data_key[1];
    }
    if (!isset($final_data_keys['sms_from_key']) || empty($final_data_keys['sms_from_key']) || $final_data_keys['sms_from_key'] === 'ApiExpectedFromKeyHere') {
      $form_state->setErrorByName('data_keys', $this->t('Api data key @key is missing', ['@key' => 'sms_from_key']));
    }
    if (!isset($final_data_keys['sms_to_key']) || empty($final_data_keys['sms_to_key']) || $final_data_keys['sms_to_key'] === 'ApiExpectedToKeyHere') {
      $form_state->setErrorByName('data_keys', $this->t('Api data key @key is missing', ['@key' => 'sms_to_key']));
    }
    if (!isset($final_data_keys['sms_body_key']) || empty($final_data_keys['sms_body_key']) || $final_data_keys['sms_body_key'] === 'ApiExpectedBodyKeyHere') {
      $form_state->setErrorByName('data_keys', $this->t('Api data key @key is missing', ['@key' => 'sms_body_key']));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $authorization = $form_state->getValue('authorization');
    $api_access_token = $form_state->getValue('api_access_token');
    $api_key_add_to = $form_state->getValue('api_key_add_to');
    $api_key_query_param_name = $form_state->getValue('api_key_query_param_name');
    $api_key_header_prefix = $form_state->getValue('api_key_header_prefix');
    $basic_auth_username = $form_state->getValue('basic_auth_username');
    $basic_auth_password = $form_state->getValue('basic_auth_password');
    $api_content_type = $form_state->getValue('api_content_type');

    // Clean unused config.
    $clean_all_auth = FALSE;
    switch ($authorization) {
      case 'api_key':
        $basic_auth_username = '';
        $basic_auth_password = '';
        if ($api_key_add_to === 'header') {
          $api_key_query_param_name = '';
        }
        else {
          $api_key_header_prefix = '';
        }
        break;

      case 'bearer':
        $api_key_add_to = '';
        $api_key_query_param_name = '';
        $api_key_header_prefix = '';
        $basic_auth_username = '';
        $basic_auth_password = '';
        break;

      case 'basic':
        $api_access_token = '';
        $api_key_add_to = '';
        $api_key_query_param_name = '';
        $api_key_header_prefix = '';
        break;

      default:
        $clean_all_auth = TRUE;
        break;
    }

    // Format API data keys.
    $data_keys = $form_state->getValue('data_keys');
    $data_keys = preg_split('/\r\n|\r|\n/', $data_keys);
    $final_data_keys = [];
    foreach ($data_keys as $data_key) {
      $data_key = explode('|', $data_key);
      $final_data_keys[$data_key[0]] = $data_key[1];
    }

    // Get module config editable.
    $config = \Drupal::configFactory()->getEditable('vactory_sms_sender.settings');
    $config->set('endpoint', $form_state->getValue('endpoint'))
      ->set('from', $form_state->getValue('from'))
      ->set('authorization', $authorization)
      ->set('api_access_token', !$clean_all_auth ? $api_access_token : '')
      ->set('api_key_add_to', !$clean_all_auth ? $api_key_add_to : '')
      ->set('api_key_query_param_name', !$clean_all_auth ? $api_key_query_param_name : '')
      ->set('api_key_header_prefix', !$clean_all_auth ? $api_key_header_prefix : '')
      ->set('basic_auth_username', !$clean_all_auth ? $basic_auth_username : '')
      ->set('basic_auth_password', !$clean_all_auth ? $basic_auth_password : '')
      ->set('api_content_type', $api_content_type)
      ->set('data_keys', $final_data_keys)
      ->save();
  }

  /**
   * Get API data default keys.
   */
  public function getApiDataKeysDefaultValue() {
    return <<<EOD
      sms_from_key|ApiExpectedFromKeyHere
      sms_to_key|ApiExpectedToKeyHere
      sms_body_key|ApiExpectedBodyKeyHere
      EOD;
  }

  /**
   * Auto configure module depending to SMS API provider Twilio or Infobip.
   */
  public function autoConfigurateSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $name = isset($triggering_element['#name']) ? $triggering_element['#name'] : '';
    if (!empty($name) && in_array($name, ['infobip', 'twilio'])) {
      if ($name === 'infobip') {
        // Infobip Settings example.
        $endpoint = '6jj25r.api.infobip.com/sms/2/text/single';
        $authorization = 'api_key';
        $api_access_token = '5bde32fffdcdd60d4b0cd1667f9fc137-68369714-1124-4bf7-b8a8-ba3fb0d48c6c';
        $api_key_add_to = 'header';
        $api_key_query_param_name = '';
        $api_key_header_prefix = 'App';
        $basic_auth_username = '';
        $basic_auth_password = '';
        $api_content_type = 'json';
        $from = 'Vactory';
        $final_data_keys = [
          'sms_from_key' => 'from',
          'sms_to_key' => 'to',
          'sms_body_key' => 'text',
        ];
      }
      else {
        // Twilio Settings example.
        $endpoint = 'https://api.twilio.com/2010-04-01/Accounts/ACac4507c5572532b223b585ca9e3e8d70/Messages.json';
        $authorization = 'basic';
        $api_access_token = '';
        $api_key_add_to = '';
        $api_key_query_param_name = '';
        $api_key_header_prefix = '';
        $basic_auth_username = 'ACac4507c5572532b223b585ca9e3e8d70';
        $basic_auth_password = '37a6c8f5fc453758da6a3d0548db2da3';
        $api_content_type = 'form_params';
        $from = 'Vactory';
        $final_data_keys = [
          'sms_from_key' => 'From',
          'sms_to_key' => 'To',
          'sms_body_key' => 'Body',
        ];
      }

      $config = \Drupal::configFactory()->getEditable('vactory_sms_sender.settings');
      $config->set('endpoint', $endpoint)
        ->set('from', $from)
        ->set('authorization', $authorization)
        ->set('api_access_token', $api_access_token)
        ->set('api_key_add_to', $api_key_add_to)
        ->set('api_key_query_param_name', $api_key_query_param_name)
        ->set('api_key_header_prefix', $api_key_header_prefix)
        ->set('basic_auth_username', $basic_auth_username)
        ->set('basic_auth_password', $basic_auth_password)
        ->set('api_content_type', $api_content_type)
        ->set('data_keys', $final_data_keys)
        ->save();

    }
  }

}
