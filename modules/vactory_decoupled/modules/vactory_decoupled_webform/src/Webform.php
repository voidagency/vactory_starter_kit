<?php

namespace Drupal\vactory_decoupled_webform;


use Drupal\webform\Element\WebformTermReferenceTrait;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformTokenManager;

/**
 * Simplifies the process of generating an API version of a webform.
 *
 * @api
 */
class Webform
{
  use WebformTermReferenceTrait;

  protected $webform;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManager
   */
  protected $webformTokenManager;

  public function __construct(WebformTokenManager $webformTokenManager) {
    $this->webformTokenManager = $webformTokenManager;
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param $webform_id
   * @return array
   *   The JSON structure of the requested resource.
   */
  public function normalize($webform_id)
  {
    $this->webform = \Drupal\webform\Entity\Webform::load($webform_id);
    $elements = $this->webform->getElementsInitialized();
    return $this->itemsToSchema($elements);
  }

  /**
   * Creates a JSON Schema out of Webform Items.
   *
   * @param $items
   * @return array
   */
  public function itemsToSchema($items)
  {
    $schema = [];

    foreach ($items as $key => $item) {
      if (isset($item['#type']) && $item['#type'] === 'webform_actions') {
        $schema['buttons']['actions'][$key] = $this->SubmitbuttonsToUiSchema($item);
        continue;
      }
      $schema[$key] = $this->itemToUiSchema($key, $item, $items);
    }

    // Add reset button.
    $schema['buttons']['reset'] = $this->resetButtonToUiSchema();
    return $schema;
  }

  /**
   * Add reset button to ui schema.
   *
   * @return array
   */
  public function resetButtonToUiSchema() {
    $properties = [];
    $properties['hidden'] = !$this->webform->getSetting('form_reset');
    $properties['text'] = t('Reset');
    return $properties;
  }

  /**
   * Add Buttons to ui schema.
   *
   * @param $item
   *
   * @return array
   */
  public function SubmitbuttonsToUiSchema($item) {
    $properties = [];
    $properties['text'] = isset($item['#submit__label']) ? $item['#submit__label'] : (isset($item['#title']) ? $item['#title'] : '');
    $properties['type'] = $item['#type'];
    return $properties;
  }

  /**
   * Creates a UI Schema out of a Webform Item.
   *
   * @param $field_name
   * @param $item
   * @param $items
   * @return array
   */
  private function itemToUiSchema($field_name, $item, $items)
  {
    $properties = [];
    if (isset($item['#required']) || isset($item['#pattern'])) {
      $properties['validation'] = [];
    }

    if (isset($item['#default_value'])) {
      $properties['default_value'] = $this->webformTokenManager->replace($item['#default_value'], NULL, [], []);
    }

    if (isset($item['#title_display'])) {
      $properties['title_display'] = $item['#title_display'];
    }

    // @todo: webform_terms_of_service

    $types = [
      'textfield' => 'text',
      'email' => 'text',
      'webform_email_confirm' => 'text',
      'url' => 'text',
      'tel' => 'text',
      'hidden' => 'text',
      'number' => 'number',
      'textarea' => 'textArea',
      'captcha' => 'captcha',
      'checkbox' => 'checkbox',
      'webform_terms_of_service' => 'checkbox',
      'select' => 'select',
      'webform_select_other' => 'select',
      'webform_term_select' => 'select',
      'radios' => 'radios',
      'webform_radios_other' => 'radios',
      'checkboxes' => 'checkboxes',
      'webform_buttons' => 'checkboxes',
      'webform_buttons_other' => 'checkboxes',
      'webform_checkboxes_other' => 'checkboxes',
      'webform_document_file' => 'upload',
      'webform_image_file' => 'upload',
      'date' => 'date',
      'webform_time' => 'time',
      'processed_text' => 'rawhtml',
    ];

    $htmlInputTypes = [
      'tel' => 'tel',
      'hidden' => 'hidden'
    ];

    $type = $item['#type'];
    $ui_type = $types[$type];
    $properties['type'] = $ui_type;
    (isset($item['#title']) && !is_null($item['#title'])) ? $properties['label'] = $item['#title'] : NULL;
    (isset($item['#placeholder']) && !is_null($item['#placeholder'])) ? $properties['placeholder'] = (string)t($item['#placeholder']) : NULL;
    (isset($item['#description']) && !is_null($item['#description'])) ? $properties['helperText'] = (string)t($item['#description']) : NULL;
    (isset($item['#readonly']) && !is_null($item['#readonly'])) ? $properties['readOnly'] = $item['#readonly'] : NULL;
    (isset($htmlInputTypes[$type]) && !is_null($htmlInputTypes[$type])) ? $properties['htmlInputType'] = $htmlInputTypes[$type] : NULL;
    (isset($item['#options']) && !is_null($item['#options'])) ? $properties['options'] = $this->formatOptions($item['#options'] ?? []) : NULL;
    (isset($item['#empty_option']) && !is_null($item['#empty_option'])) ? $properties['emptyOption'] = (string)t($item['#empty_option']) : NULL;
    (isset($item['#empty_value']) && !is_null($item['#empty_value'])) ? $properties['emptyValue'] = $item['#empty_value'] : NULL;
    (isset($item['#options_display']) && !is_null($item['#options_display'])) ? $properties['optionsDisplay'] = $item['#options_display'] : NULL;
    (isset($item['#options_all']) && !is_null($item['#options_all'])) ? $properties['optionsAll'] = $item['#options_all'] : NULL;
    (isset($item['#options_none']) && !is_null($item['#options_none'])) ? $properties['optionsNone'] = $item['#options_none'] : NULL;

    if (isset($item['#required'])) {
      $properties['validation']['required'] = TRUE;
      (isset($item['#required_error']) && !is_null($item['#required_error'])) ? $properties['validation']['requiredError'] = (string)t($item['#required_error']) : NULL;
    }

    if (isset($item['#pattern'])) {
      $properties['validation']['pattern'] = $item['#pattern'];
      (isset($item['#pattern_error']) && !is_null($item['#pattern_error'])) ? $properties['validation']['patternError'] = $item['#pattern_error'] : NULL;
    }

    if (isset($item['#min'])) {
      $properties['validation']['min'] = $item['#min'];
    }
    if (isset($item['#max'])) {
      $properties['validation']['max'] = $item['#max'];
    }

    if (
      $type === 'email' ||
      $type === 'webform_email_confirm'
    ) {
      if (!isset($properties['validation']['pattern'])) {
        $properties['validation']['pattern'] = "/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i";
        $properties['validation']['patternError'] = (string)t("Le champ @field n'est pas valide", [
          '@field' => $properties['label']
        ]);
      }
    }

    if ($type === 'webform_email_confirm') {
      foreach ($items as $key => $element) {
        if (isset($element['#type']) && $element['#type'] === 'email') {
          $properties['validation']['sameAs'] = $key;
          $properties['validation']['sameAsError'] = (string)t("Le champ @field n'est pas valide", [
            '@field' => $properties['label']
          ]);
          break;
        }
      }
    }

    if ($type === 'webform_term_select') {
      if (empty($item['#vocabulary'])) {
        $properties['options'] = [];
      }

      if (!empty($element['#breadcrumb'])) {
        $properties['options'] = $this->formatOptions(static::getOptionsBreadcrumb($item, ''));
      } else {
        $properties['options'] = $this->formatOptions(static::getOptionsTree($item, ''));
      }
    }

    if (
      isset($properties['emptyOption']) &&
      !empty($properties['emptyOption']) &&
      !empty($properties['options'])
    ) {
      $emptyOption = [
        'label' => $properties['emptyOption'],
        'value' => ''
      ];

      if (
        isset($properties['emptyValue']) &&
        !empty($properties['emptyValue'])
      ) {
        $emptyOption['value'] = $properties['emptyValue'];
      }

      array_unshift($properties['options'], $emptyOption);
    }

    if ($type === 'captcha') {
      $properties['validation']['required'] = TRUE;
    }

    if ($ui_type === 'upload') {
      $element = $this->webform->getElement($field_name);
      $webform_submission = WebformSubmission::create([
        'webform_id' => $this->webform->id(),
      ]);
      // Prepare upload location and validators for the element
      $element_plugin = $this->getElementPlugin($element);
      $element_plugin->prepare($element, $webform_submission);

      $properties['isMultiple'] = isset($item['#multiple']);
      if (isset($item['#multiple']) && is_integer($item['#multiple'])) {
        $properties['validation']['maxFiles'] = $item['#multiple'];
      }

      if (isset($item['#max_filesize'])) {
        $properties['validation']['maxSizeBytes'] = 1024 * 1024 * intval($item['#max_filesize']);
        $properties['maxSizeMb'] = intval($item['#max_filesize']);
      }

      if (
        isset($element['#upload_validators']) &&
        isset($element['#upload_validators']['file_validate_extensions'][0])
      ) {
        $field_extensions = $element['#upload_validators']['file_validate_extensions'][0];
        $extensions = explode(" ", $field_extensions);
        $doted_extensions = [];
        foreach ($extensions as $ext) {
          array_push($doted_extensions, "." . $ext);
        }
        $filenamed_extensions = join(",", $doted_extensions);
        $properties['validation']['extensions'] = $filenamed_extensions;
        $properties['extensionsClean'] = $field_extensions;
      }

    }

    if ($ui_type === 'rawhtml') {
      $properties['html'] = $item['#text'];
      $properties['format'] = $item['#format'];
      $properties['attributes'] = $item['#wrapper_attributes'];
    }

    return $properties;
  }

  /**
   * @param $items
   * @param bool $reverse
   * @return array
   */
  private function formatOptions($items, $reverse = FALSE)
  {
    $options = [];

    foreach ($items as $value => $label) {
      array_push($options, [
        'value' => $value,
        'label' => $label,
      ]);
    }

    return $options;
  }

  /**
   * Loads the webform element plugin for the provided element.
   *
   * @param array $element
   *   The element for which to get the plugin.
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getElementPlugin(array $element)
  {
    /** @var \Drupal\Core\Render\ElementInfoManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.webform.element');
    $plugin_definition = $plugin_manager->getDefinition($element['#type']);

    $element_plugin = $plugin_manager->createInstance($element['#type'], $plugin_definition);

    return $element_plugin;
  }


}
