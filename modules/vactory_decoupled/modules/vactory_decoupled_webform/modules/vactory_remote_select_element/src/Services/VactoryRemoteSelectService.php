<?php

namespace Drupal\vactory_remote_select_element\Services;

/**
 * Class Vactory remote select service.
 */
class VactoryRemoteSelectService {

  /**
   * Execute request to get options from endpoint.
   */
  public function executeRequest(&$element) {
    $client = \Drupal::httpClient();
    $data_key = isset($element['#data_key']) ? $element['#data_key'] : '';
    $data_value = isset($element['#data_value']) ? $element['#data_value'] : '';

    $headers = isset($element['#headers']) ? json_decode(str_replace(["\r", "\n"], '', $element['#headers']), TRUE) : '';

    // Replace tokens in endpoint.
    $endpoint = $element['#endpoint'];
    $token_options = ['clear' => TRUE, 'callback' => '_webform_remote_select_token_cleaner'];
    $token_data = [];
    $endpoint = \Drupal::token()->replace($endpoint, $token_data, $token_options);

    try {
      $response = $client->get($endpoint, [
        'headers' => !empty($headers) ? $headers : [],
      ]);

      $body = $response->getBody();
      if (empty($body)) {
        return FALSE;
      }

      $data = $body->getContents();
      $data = empty($data) ? '' : json_decode($data, TRUE);
      if (empty($data)) {
        return FALSE;
      } else {
        if (isset($element['#response_key']) && !empty($element['#response_key'])) {
          $data = $this->getJsonValue($data, $element['#response_key']);
        }
        if (!self::isAssoc($data)) {
          if (!is_array($data[0])) {
            foreach ($data as $item) {
              $element['#options'][$item] = $item;
            }
          } else {
            if (self::isAssoc($data[0]) && !empty($data_key) && !empty($data_value)) {
              foreach ($data as $item) {
                $element['#options'][$item[$data_key]] = $item[$data_value];
              }
            } else {
              return FALSE;
            }
          }
        } else {
          foreach ($data as $k => $item) {
            if (is_array($item)) {
              $value = $item[$data_value];
            } else {
              $value = $item;
            }
            $element['#options'][$item[$data_key]] = $value;
          }
        }
      }
      return $element;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Is assoc.
   */
  private static function isAssoc(array $arr) {
    if (array() === $arr) {
      return false;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * Gets value from JSON array.
   *
   * @param array $retval
   *   Original array from which the value is obtained.
   * @param string $string
   *   Path to value.
   *
   * @return string
   *   Result value from specific key/path inside the JSON array.
   */
  private function getJsonValue(array $retval, $string) {
    $parts = explode('.', $string);
    foreach ($parts as $part) {
      $retval = (array_key_exists($part, $retval) ? $retval[$part] : $retval);
    }
    return $retval;
  }

}
