<?php

namespace Drupal\vactory_icon\Plugin\VactoryIconProvider;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_icon\VactoryIconProviderBase;
use Drupal\core\Site\Settings;

/**
 * Xml source icon provider.
 *
 * @VactoryIconProvider(
 *   id="xml_icon_provider",
 *   description=@Translation("XML source")
 * )
 */
class XmlSourceIconProvider extends VactoryIconProviderBase {

  /**
   * {@inheritDoc}
   */
  public function settingsForm(ImmutableConfig|Config $config) {
    $form = [];
    $xml_source_url = $config->get('xml_source_url');

    $form['xml_source_url'] = [
      '#type' => 'textfield',
      '#title' => t('XML source url'),
      '#description' => t("XML icon source url \n Relative link must start with / otherwise use the full url"),
      '#default_value' => $xml_source_url,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsFormSubmit(FormStateInterface $form_state, ImmutableConfig|Config $config) {
    $values = $form_state->getValues();
    $xml_source_url = $values['xml_source_url'] ?? '';
    $config->set('xml_source_url', $xml_source_url)
      ->save();
  }

  /**
   * {@inheritDoc}
   */
  public function iconPickerFormElementAlter(array &$element, ImmutableConfig|Config $config) {
    $svg_ids = [];
    $svg_paths_d = [];
    $from_xml_svgs = TRUE;
    $host = !empty(getenv("BASE_FRONTEND_URL")) ? getenv("BASE_FRONTEND_URL") : (!empty(getenv("FRONTEND_URL")) ? getenv("FRONTEND_URL") : "");
    $xml_source_url = $config->get('xml_source_url');
    $xml_source_url = !preg_match("~^(?:f|ht)tps?://~i", $xml_source_url) ? $host . $xml_source_url : $xml_source_url;
    $svgs_infos = $this->fetchIcons($config);
    if (!empty($svgs_infos) && isset($svgs_infos['symbol']) && is_array($svgs_infos['symbol'])) {
      foreach ($svgs_infos['symbol'] as $info) {
        $svg_id = $info['@attributes']['id'];
        $svg_ids[] = $svg_id;
        if (!empty($info['path']) && is_array($info['path']) && count($info['path']) > 1) {
          foreach ($info['path'] as $path) {
            $svg_paths_d[$svg_id][] = $path['@attributes']['d'];
          }
        }
        else {
          $svg_paths_d[$svg_id] = $info['path']['@attributes']['d'];
        }
        $element['#options'][$svg_id] = $svg_id;
      }
    }
    $element['#attached']['drupalSettings']['vactory_icon']['from_xml_svgs'] = $from_xml_svgs;
    $element['#attached']['drupalSettings']['vactory_icon']['svg_ids'] = $svg_ids;
    $element['#attached']['drupalSettings']['vactory_icon']['svg_paths_d'] = $svg_paths_d;
    $element['#attached']['drupalSettings']['vactory_icon']['xml_source_url'] = $xml_source_url;
  }

  /**
   * {@inheritDoc}
   */
  public function iconPickerLibraryInfoAlter(array &$library_info) {
    $stylesheet = 'public://vactory_icon/style.css';
    if (file_exists($stylesheet)) {
      $stylesheet = \Drupal::service('file_url_generator')->generateString($stylesheet);
      $library_info['css']['theme'][$stylesheet] = [];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function fetchIcons(ImmutableConfig|Config $config) {
    $host = !empty(getenv("BASE_FRONTEND_URL")) ? getenv("BASE_FRONTEND_URL") : (!empty(getenv("FRONTEND_URL")) ? getenv("FRONTEND_URL") : "");
    $xml_source_url = $config->get('xml_source_url');
    $xml_source_url = !preg_match("~^(?:f|ht)tps?://~i", $xml_source_url) ? $host . $xml_source_url : $xml_source_url;
    $svgs_infos = [];
    if (!empty($xml_source_url)) {
      // Get the User-Agent from settings.
      $default_user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
      $user_agent = Settings::get('custom_user_agent', $default_user_agent);
      // Create a context with a custom User-Agent header.
      $context = stream_context_create([
        'http' => [
          'header' => "User-Agent: $user_agent\r\n",
        ],
      ]);
      // Get the XML content from the URL.
      $svgs_xml = file_get_contents($xml_source_url, FALSE, $context);
      if ($svgs_xml !== FALSE) {
        // Parse the XML content into a SimpleXMLElement object.
        $svgs_xmlObj = simplexml_load_string($svgs_xml);
        if ($svgs_xmlObj !== FALSE) {
          // Convert the SimpleXMLElement object to a JSON string.
          $svgs_json = json_encode($svgs_xmlObj);
          // Convert the JSON string to a PHP array.
          $svgs_infos = json_decode($svgs_json, TRUE);
        }
      }
      else {
        $error = error_get_last();
        \Drupal::logger('vactory_icon')->error('Error: ' . $error['message']);
      }
    }
    return $svgs_infos;
  }

}
