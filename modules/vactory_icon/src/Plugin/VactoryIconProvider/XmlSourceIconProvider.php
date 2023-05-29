<?php

namespace Drupal\vactory_icon\Plugin\VactoryIconProvider;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_icon\Annotation\VactoryIconProvider;
use Drupal\vactory_icon\VactoryIconProviderBase;

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
      '#description' => t('XML icon source url'),
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
    $xml_source_url = $config->get('xml_source_url');
    $svgs_infos = $this->fetchIcons($config);
    if (!empty($svgs_infos) && isset($svgs_infos['symbol']) && is_array($svgs_infos['symbol'])) {
      foreach ($svgs_infos['symbol'] as $info) {
        $svg_id = $info['@attributes']['id'];
        $svg_ids[] = $svg_id;
        $svg_paths_d[$svg_id] = $info['path']['@attributes']['d'];
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
    $library_info['css']['theme'][$stylesheet] = [];
  }

  /**
   * {@inheritDoc}
   */
  public function fetchIcons(ImmutableConfig|Config $config) {
    $xml_source_url = $config->get('xml_source_url');
    $svgs_infos = [];
    if (!empty($xml_source_url)) {
      // Get the XML content from the URL.
      $svgs_xml = file_get_contents($xml_source_url);
      // Parse the XML content into a SimpleXMLElement object.
      $svgs_xmlObj = simplexml_load_string($svgs_xml);
      // Convert the SimpleXMLElement object to a JSON string.
      $svgs_json = json_encode($svgs_xmlObj);
      // Convert the JSON string to a PHP array.
      $svgs_infos = json_decode($svgs_json, TRUE);
    }
    return $svgs_infos;
  }

}
