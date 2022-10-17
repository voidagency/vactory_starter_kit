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
class Webform {

  use WebformTermReferenceTrait;

  protected $webform;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManager
   */
  protected $webformTokenManager;

  const LAYOUTS = ['webform_flexbox', 'container', 'fieldset', 'details'];

  const PAGE = 'webform_wizard_page';

  public function __construct(WebformTokenManager $webformTokenManager) {
    $this->webformTokenManager = $webformTokenManager;
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param $webform_id
   *
   * @return array
   *   The JSON structure of the requested resource.
   */
  public function normalize($webform_id) {
    $this->webform = \Drupal\webform\Entity\Webform::load($webform_id);
    $elements = $this->webform->getElementsInitialized();
    $schema = $this->itemsToSchema($elements);
    // Add reset button.
    $schema['buttons']['reset'] = $this->resetButtonToUiSchema();
    return $schema;
  }

  /**
   * Creates a JSON Schema out of Webform Items.
   *
   * @param $items
   *
   * @return array
   */
  public function itemsToSchema($items) {
    $schema = [];
    foreach ($items as $key => $item) {
      if (isset($item['#type']) && $item['#type'] === 'webform_actions') {
        $schema['buttons']['actions'][$key] = $this->SubmitbuttonsToUiSchema($item);
        continue;
      }
      if (in_array($item['#type'], self::LAYOUTS)) {
        $schema[$key] = $this->layoutToUiSchema($key, $item, $items);
      }
      else {
        if ($item['#type'] === self::PAGE) {
          $schema['pages'][$key] = $this->PagesToUiSchema($key, $item, $items);
        }
        else {
          $schema[$key] = $this->itemToUiSchema($key, $item, $items);
        }
      }
    }

    if (array_key_exists('pages', $schema)) {
      $schema['pages']['settings']['preview']['enable'] = isset($this->webform->getSettings()['preview']) && !empty($this->webform->getSettings()['preview']) ? $this->webform->getSettings()['preview'] > 0 : FALSE;
      $schema['pages']['settings']['preview']['label'] = isset($this->webform->getSettings()['preview_label']) && !empty($this->webform->getSettings()['preview_label']) ? $this->webform->getSettings()['preview_label'] : '';
      $schema['pages']['settings']['preview']['title'] = isset($this->webform->getSettings()['preview_title']) && !empty($this->webform->getSettings()['preview_title']) ? $this->webform->getSettings()['preview_title'] : '';
      $schema['pages']['settings']['preview']['message'] = isset($this->webform->getSettings()['preview_message']) && !empty($this->webform->getSettings()['preview_message']) ? $this->webform->getSettings()['preview_message'] : '';
      $schema['pages']['settings']['preview']['excluded_elements'] = isset($this->webform->getSettings()['preview_excluded_elements']) && !empty($this->webform->getSettings()['preview_excluded_elements']) ? $this->webform->getSettings()['preview_excluded_elements'] : [];
      $schema['pages']['settings']['preview']['excluded_elements'] = isset($this->webform->getSettings()['preview_excluded_elements']) && !empty($this->webform->getSettings()['preview_excluded_elements']) ? $this->webform->getSettings()['preview_excluded_elements'] : [];
      $schema['pages']['settings']['wizard']['prev_button_label'] = isset($this->webform->getSettings()['wizard_prev_button_label']) && !empty($this->webform->getSettings()['wizard_prev_button_label']) ? $this->webform->getSettings()['wizard_prev_button_label'] : '';
      $schema['pages']['settings']['wizard']['next_button_label'] = isset($this->webform->getSettings()['wizard_next_button_label']) && !empty($this->webform->getSettings()['wizard_next_button_label']) ? $this->webform->getSettings()['wizard_next_button_label'] : '';
    }
//    $schema['draft']['settings']['draft'] = isset($this->webform->getSettings()['draft']) && !empty($this->webform->getSettings()['draft']) ? $this->webform->getSettings()['draft'] : 'none';
//    $schema['draft']['settings']['draft_auto_save'] = isset($this->webform->getSettings()['draft']) && !empty($this->webform->getSettings()['draft_auto_save']) ? $this->webform->getSettings()['draft_auto_save'] : FALSE;

    return $schema;
  }

  /**
   * Layouts to ui schema.
   */
  public function layoutToUiSchema($key, $item, $items) {
    $fields = [];
    $flexTotal = 0;
    foreach ($items[$key] as $key => $field) {
      if (strpos($key, "#") !== 0) {
        if (array_key_exists('#webform_parent_flexbox', $field) && $field['#webform_parent_flexbox']) {
          $flexTotal += array_key_exists('#flex', $field) ? $field['#flex'] : 1;
        }
        $fields[$key] = $field;
      }
    }

    $properties = [
      'type' => $item['#type'],
      'title' => $item['#title'] ?? '',
    ];

    if (isset($item['#align_items'])) {
      $properties['align_items'] = $item['#align_items'];
    }

    if (isset($item['#title_display'])) {
      $properties['title_display'] = $item['#title_display'];
    }

    if (isset($item['#description'])) {
      $properties['description'] = $item['#description'];

      if (isset($item['#description_display'])) {
        $properties['description_display'] = $item['#description_display'];
      }
    }

    if (array_key_exists('#webform_parent_flexbox', $item) && $item['#webform_parent_flexbox']) {
      $properties['flex'] = array_key_exists('#flex', $item) ? $item['#flex'] : 1;
    }

    (isset($item['#attributes']['class']) && !empty($item['#attributes']['class'])) ? $properties['class'] = implode(" ", $item['#attributes']['class']) : "";

    if ($fields !== []) {
      $properties['childs'] = $this->itemsToSchema($fields);
      if ($flexTotal > 1) {
        $properties['childs']['flexTotal'] = $flexTotal;
      }
    }

    return $properties;
  }

  /**
   * Layouts to ui schema.
   */
  public function PagesToUiSchema($key, $item, $items) {
    $fields = [];
    foreach ($items[$key] as $key => $field) {
      if (strpos($key, "#") !== 0) {
        $fields[$key] = $field;
      }
    }

    $properties = [
      'type' => $item['#type'],
      'title' => $item['#title'] ?? '',
    ];

    (isset($item['#attributes']['class']) && !empty($item['#attributes']['class'])) ? $properties['class'] = implode(" ", $item['#attributes']['class']) : "";
    (isset($item['#prev_button_label']) && !empty($item['#prev_button_label'])) ? $properties['prev_button_label'] = $item['#prev_button_label'] : NULL;
    (isset($item['#next_button_label']) && !empty($item['#next_button_label'])) ? $properties['next_button_label'] = $item['#next_button_label'] : NULL;

    if ($fields !== []) {
      $properties['childs'] = $this->itemsToSchema($fields);
    }

    return $properties;
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
   *
   * @return array
   */
  private function itemToUiSchema($field_name, $item, $items) {
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
      'hidden' => 'hidden',
    ];

    $type = $item['#type'];
    $ui_type = $types[$type];
    $properties['type'] = $ui_type;
    (isset($item['#title']) && !is_null($item['#title'])) ? $properties['label'] = $item['#title'] : NULL;
    (array_key_exists('#webform_parent_flexbox', $item) && $item['#webform_parent_flexbox']) ? $properties['flex'] = (array_key_exists('#flex', $item) ? $item['#flex'] : 1) : 1;
    (isset($item['#placeholder']) && !is_null($item['#placeholder'])) ? $properties['placeholder'] = (string) t($item['#placeholder']) : NULL;
    (isset($item['#description']) && !is_null($item['#description'])) ? $properties['helperText'] = (string) t($item['#description']) : NULL;
    (isset($item['#readonly']) && !is_null($item['#readonly'])) ? $properties['readOnly'] = $item['#readonly'] : NULL;
    (isset($htmlInputTypes[$type]) && !is_null($htmlInputTypes[$type])) ? $properties['htmlInputType'] = $htmlInputTypes[$type] : NULL;
    (isset($item['#options']) && !is_null($item['#options'])) ? $properties['options'] = $this->formatOptions($item['#options'] ?? []) : NULL;
    (isset($item['#empty_option']) && !is_null($item['#empty_option'])) ? $properties['emptyOption'] = (string) t($item['#empty_option']) : NULL;
    (isset($item['#empty_value']) && !is_null($item['#empty_value'])) ? $properties['emptyValue'] = $item['#empty_value'] : NULL;
    (isset($item['#options_display']) && !is_null($item['#options_display'])) ? $properties['optionsDisplay'] = $item['#options_display'] : NULL;
    (isset($item['#options_all']) && !is_null($item['#options_all'])) ? $properties['optionsAll'] = $item['#options_all'] : NULL;
    (isset($item['#options_none']) && !is_null($item['#options_none'])) ? $properties['optionsNone'] = $item['#options_none'] : NULL;
    (isset($item['#attributes']['class']) && !empty($item['#attributes']['class'])) ? $properties['class'] = implode(" ", $item['#attributes']['class']) : "";

    if (isset($item['#required'])) {
      $properties['validation']['required'] = TRUE;
      (isset($item['#required_error']) && !is_null($item['#required_error'])) ? $properties['validation']['requiredError'] = (string) t($item['#required_error']) : NULL;
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
        $properties['validation']['patternError'] = (string) t("Le champ @field n'est pas valide", [
          '@field' => $properties['label'],
        ]);
      }
    }

    if ($type === 'webform_email_confirm') {
      foreach ($items as $key => $element) {
        if (isset($element['#type']) && $element['#type'] === 'email') {
          $properties['validation']['sameAs'] = $key;
          $properties['validation']['sameAsError'] = (string) t("Le champ @field n'est pas valide", [
            '@field' => $properties['label'],
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
      }
      else {
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
        'value' => '',
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
   *
   * @return array
   */
  private function formatOptions($items, $reverse = FALSE) {
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
  protected function getElementPlugin(array $element) {
    /** @var \Drupal\Core\Render\ElementInfoManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.webform.element');
    $plugin_definition = $plugin_manager->getDefinition($element['#type']);

    $element_plugin = $plugin_manager->createInstance($element['#type'], $plugin_definition);

    return $element_plugin;
  }


}
