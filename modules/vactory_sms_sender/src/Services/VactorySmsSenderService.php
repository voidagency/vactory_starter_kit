<?php

namespace Drupal\vactory_sms_sender\Services;

/**
 * Vactory SMS Sender Service.
 */
class VactorySmsSenderService {

  /**
   * Send SMS Message callback.
   */
  public function sendSms($to, $message, $allowThrowingExceptions = FALSE) {
    $client = \Drupal::httpClient();

    // Get module configuration.
    $config = \Drupal::config('vactory_sms_sender.settings');
    $options = [];
    $endpoint = $config->get('endpoint');
    $from = $config->get('from');
    $authorization = $config->get('authorization');
    $api_access_token = $config->get('api_access_token');
    $api_key_add_to = $config->get('api_key_add_to');
    $api_key_query_param_name = $config->get('api_key_query_param_name');
    $api_key_header_prefix = $config->get('api_key_header_prefix');
    $basic_auth_username = $config->get('basic_auth_username');
    $basic_auth_password = $config->get('basic_auth_password');
    $api_content_type = $config->get('api_content_type');
    $data_keys = $config->get('data_keys');

    // Prepare Authorization part.
    if ($authorization === 'api_key') {
      if ($api_key_add_to === 'query') {
        $options['query'] = [$api_key_query_param_name => $api_access_token];
      }
      else {
        $options['headers']['Authorization'] = $api_key_header_prefix . ' ' . $api_access_token;
      }
    }
    if ($authorization === 'bearer') {
      $options['headers']['Authorization'] = 'Bearer ' . $api_access_token;
    }
    if ($authorization === 'basic') {
      $options['headers']['Authorization'] = 'Basic ' . base64_encode($basic_auth_username . ':' . $basic_auth_password);
    }

    // Prepare Data.
    $data = [
      $data_keys['sms_from_key'] => $from,
      $data_keys['sms_to_key'] => $to,
      $data_keys['sms_body_key'] => $message,
    ];

    // Prepare data type headers.
    if ($api_content_type === 'json') {
      $options['json'] = $data;
      $options['headers']['Content-Type'] = 'application/json';
      $options['headers']['Accept'] = 'application/json';
    }
    else {
      $options['form_params'] = $data;
      $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    try {
      $client->post($endpoint, $options);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_sms_sender')
        ->error("Erreur lors de l'envoi de SMS : " . $e->getMessage());
      if ($allowThrowingExceptions) {
        throw $e;
      }
    }

    return FALSE;

  }

}
