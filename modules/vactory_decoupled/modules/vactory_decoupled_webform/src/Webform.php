<?php

namespace Drupal\vactory_decoupled_webform;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformTermReferenceTrait;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformElementManager;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformTokenManager;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\vactory_private_files_decoupled\VactoryPrivateFilesServices;

/**
 * Simplifies the process of generating an API version of a webform.
 *
 * @api
 */
class Webform {

  use WebformTermReferenceTrait;

  /**
   * Webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Webform default values.
   *
   * @var array
   */
  protected $defaultValues = [];

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManager
   */
  protected $webformTokenManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManager
   */
  protected $webformElementManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The vactory private file service.
   *
   * @var \Drupal\vactory_private_files_decoupled\VactoryPrivateFilesServices
   */
  protected $vactoryPrivateFilesService;

  /**
   * Form with test values.
   *
   * @var array
   */
  protected $formWithTestsValues = [];

  /**
   * Layouts constant.
   */
  const LAYOUTS = [
    'webform_flexbox',
    'container',
    'fieldset',
    'details',
    'webform_section',
  ];

  /**
   * Webform Page Constant.
   */
  const PAGE = 'webform_wizard_page';

  /**
   * The excluded webform from test.
   */
  const WEBFORM_TESTS_EXCLUDED = [
    'vactory_espace_prive_edit',
    'vactory_espace_prive_register',
  ];

  /**
   * {@inheritDoc}
   */
  public function __construct(
    WebformTokenManager $webformTokenManager,
    AccountProxy $accountProxy,
    WebformElementManager $webformElementManager,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler,
    RendererInterface $renderer,
    VactoryPrivateFilesServices $vactoryPrivateFilesService
  ) {
    $this->webformTokenManager = $webformTokenManager;
    $this->currentUser = $accountProxy->getAccount();
    $this->webformElementManager = $webformElementManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->vactoryPrivateFilesService = $vactoryPrivateFilesService;
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param string $webform_id
   *   The webform id.
   *
   * @return array
   *   The JSON structure of the requested resource.
   */
  public function normalize($webform_id) {
    $this->webform = WebformEntity::load($webform_id);
    if ($this->checkIfWeShouldPrepareTestsValues()) {
      $webformAux = $this->webform;
      $this->formWithTestsValues = $this->renderer->executeInRenderContext(new RenderContext(), static function () use ($webformAux) {
        $submission_form = $webformAux->getSubmissionForm([], 'test');
        return $submission_form['elements'] ?? [];
      });
    }
    $elements = $this->webform->getElementsInitialized();
    $draft = $this->draftSettingsToSchema();
    $schema = $this->itemsToSchema($elements);
    if (isset($schema['pages'])) {
      if (isset($draft['sid'])) {
        $schema['draft']['current_page'] = array_key_exists($draft['currentPage'], $schema['pages']) ? $i = array_search($draft['currentPage'], array_keys($schema['pages'])) : 0;
      }
      if (count($draft) > 0) {
        $schema['draft'] = $draft;
      }
    }
    // Add reset button.
    $schema['buttons']['reset'] = $this->resetButtonToUiSchema();
    $this->moduleHandler->alter('decoupled_webform_schema', $schema, $webform_id);
    return $schema;
  }

  /**
   * Return draft settings to schema.
   */
  public function draftSettingsToSchema() {
    if ($this->currentUser->isAnonymous()) {
      return [];
    }
    $webform_settings = $this->webform->getSettings();
    if (!isset($webform_settings['draft']) || empty($webform_settings['draft'])) {
      return [];
    }

    if ($webform_settings['draft'] !== 'authenticated') {
      return [];

    }

    $draft['enable'] = TRUE;
    $submissions = $this->entityTypeManager->getStorage('webform_submission')
      ->loadByProperties([
        'uid'        => $this->currentUser->id(),
        'webform_id' => $this->webform->id(),
        'in_draft'   => TRUE,
      ]);
    $submission = reset($submissions);
    if ($submission) {
      $this->defaultValues = $submission->getRawData();
      $draft['currentPage'] = $submission->getCurrentPage();
      $draft['sid'] = $submission->id();
    }
    return $draft;

  }

  /**
   * Creates a JSON Schema out of Webform Items.
   *
   * @param array $items
   *   Webform items.
   *
   * @return array
   *   Related schema.
   */
  public function itemsToSchema(array $items) {
    $schema = [];
    foreach ($items as $key => $item) {
      if (isset($item['#type']) && $item['#type'] === 'webform_actions') {
        $schema['buttons']['actions'][$key] = $this->submitbuttonsToUiSchema($item);
        continue;
      }
      if (in_array($item['#type'], self::LAYOUTS)) {
        $schema[$key] = $this->layoutToUiSchema($key, $item, $items);
      }
      else {
        if ($item['#type'] === self::PAGE) {
          $schema['pages'][$key] = $this->pagesToUiSchema($key, $item, $items);
        }
        else {
          $schema[$key] = $this->itemToUiSchema($key, $item, $items);
        }
      }
    }

    if (array_key_exists('pages', $schema)) {
      $webform_settings = $this->webform->getSettings();
      $schema['pages']['webform_preview']['preview']['enable'] = isset($webform_settings['preview']) && !empty($webform_settings['preview']) ? $webform_settings['preview'] > 0 : FALSE;
      $schema['pages']['webform_preview']['preview']['label'] = isset($webform_settings['preview_label']) && !empty($webform_settings['preview_label']) ? $webform_settings['preview_label'] : '';
      $schema['pages']['webform_preview']['preview']['title'] = isset($webform_settings['preview_title']) && !empty($webform_settings['preview_title']) ? $webform_settings['preview_title'] : '';
      $schema['pages']['webform_preview']['preview']['message'] = isset($webform_settings['preview_message']) && !empty($webform_settings['preview_message']) ? $webform_settings['preview_message'] : '';
      $schema['pages']['webform_preview']['preview']['excluded_elements'] = isset($webform_settings['preview_excluded_elements']) && !empty($webform_settings['preview_excluded_elements']) ? $webform_settings['preview_excluded_elements'] : [];
      $schema['pages']['webform_preview']['preview']['excluded_elements'] = isset($webform_settings['preview_excluded_elements']) && !empty($webform_settings['preview_excluded_elements']) ? $webform_settings['preview_excluded_elements'] : [];
      $schema['pages']['webform_preview']['preview']['preview_exclude_empty'] = isset($webform_settings['preview_exclude_empty']) && !empty($webform_settings['preview_exclude_empty']) ? $webform_settings['preview_exclude_empty'] : FALSE;
      $schema['pages']['webform_preview']['wizard']['prev_button_label'] = isset($webform_settings['wizard_prev_button_label']) && !empty($webform_settings['wizard_prev_button_label']) ? $webform_settings['wizard_prev_button_label'] : '';
      $schema['pages']['webform_preview']['wizard']['next_button_label'] = isset($webform_settings['wizard_next_button_label']) && !empty($webform_settings['wizard_next_button_label']) ? $webform_settings['wizard_next_button_label'] : '';
    }
    /*    $schema['draft']['settings']['draft'] = isset($this->webform->getSettings()['draft']) && !empty($this->webform->getSettings()['draft']) ? $this->webform->getSettings()['draft'] : 'none';*/
    /*    $schema['draft']['settings']['draft_auto_save'] = isset($this->webform->getSettings()['draft']) && !empty($this->webform->getSettings()['draft_auto_save']) ? $this->webform->getSettings()['draft_auto_save'] : FALSE; */
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
      'type'  => $item['#type'],
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
    (isset($item['#wrapper_attributes']['class']) && !empty($item['#wrapper_attributes']['class'])) ? $properties['wrapperClass'] = implode(" ", $item['#wrapper_attributes']['class']) : "";

    if (isset($item['#states'])) {
      $properties['states'] = $this->getFormElementStates($item);
    }

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
  public function pagesToUiSchema($key, $item, $items) {
    $fields = [];
    foreach ($items[$key] as $key => $field) {
      if (strpos($key, "#") !== 0) {
        $fields[$key] = $field;
      }
    }

    $properties = [
      'type'  => $item['#type'],
      'title' => $item['#title'] ?? '',
      'icon'  => $item['#icon'] ?? '',
    ];

    (isset($item['#attributes']['class']) && !empty($item['#attributes']['class'])) ? $properties['class'] = implode(" ", $item['#attributes']['class']) : "";
    (isset($item['#wrapper_attributes']['class']) && !empty($item['#wrapper_attributes']['class'])) ? $properties['wrapperClass'] = implode(" ", $item['#wrapper_attributes']['class']) : "";
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
   *   Related schema.
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
   * @param array $item
   *   Webform items.
   *
   * @return array
   *   Related schema.
   */
  public function submitbuttonsToUiSchema(array $item) {
    $properties = [];
    $properties['text'] = isset($item['#submit__label']) ? $item['#submit__label'] : (isset($item['#title']) ? $item['#title'] : '');
    $properties['type'] = $item['#type'];
    return $properties;
  }

  /**
   * Creates a UI Schema out of a Webform Item.
   *
   * @param string $field_name
   *   Field name.
   * @param array $item
   *   Current item.
   * @param array $items
   *   Webform items.
   *
   * @return array
   *   Related schema.
   */
  private function itemToUiSchema($field_name, array $item, array $items) {
    $properties = [];
    if (isset($item['#required']) || isset($item['#pattern'])) {
      $properties['validation'] = [];
    }

    if (isset($item['#default_value']) || isset($this->defaultValues[$field_name])) {
      $properties['default_value'] = $this->defaultValueTokensReplace($item, $field_name);
    }

    if (isset($item['#default_file'])) {
      $properties['default_value'] = $this->defaultValueTokensReplace($item, $field_name, '#default_file');
      if (!empty($properties['default_value'])) {
        $decoded = json_decode($properties['default_value']);
        $properties['default_value'] = json_last_error() === JSON_ERROR_NONE ? $decoded : $properties['default_value'];
      }
    }

    if (isset($item['#title_display'])) {
      $properties['title_display'] = $item['#title_display'];
    }

    // @todo Webform_terms_of_service.
    $types = [
      'textfield'                => 'text',
      'email'                    => 'text',
      'webform_email_confirm'    => 'text',
      'url'                      => 'text',
      'tel'                      => 'text',
      'hidden'                   => 'text',
      'number'                   => 'number',
      'textarea'                 => 'textArea',
      'captcha'                  => 'captcha',
      'checkbox'                 => 'checkbox',
      'webform_terms_of_service' => 'checkbox',
      'select'                   => 'select',
      'webform_select_other'     => 'webform_select_other',
      'webform_term_select'      => 'select',
      'webform_term_checkboxes'  => 'checkboxes',
      'radios'                   => 'radios',
      'webform_radios_other'     => 'radios',
      'checkboxes'               => 'checkboxes',
      'webform_buttons'          => 'checkboxes',
      'webform_buttons_other'    => 'checkboxes',
      'webform_checkboxes_other' => 'checkboxes',
      'webform_document_file'    => 'upload',
      'webform_image_file'       => 'upload',
      'date'                     => 'date',
      'webform_time'             => 'time',
      'processed_text'           => 'rawhtml',
      'password'                 => 'password',
      'range'                    => 'range',
    ];

    $htmlInputTypes = [
      'tel'    => 'tel',
      'hidden' => 'hidden',
    ];

    $type = $item['#type'];
    $ui_type = $types[$type] ?? NULL;
    $properties['type'] = $ui_type;
    // phpcs:disable
    (isset($item['#title']) && !is_null($item['#title'])) ? $properties['label'] = $item['#title'] : NULL;
    (isset($item['#other__title']) && !is_null($item['#other__title'])) ? $properties['otherTitle'] = $item['#other__title'] : NULL;
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
    (isset($item['#wrapper_attributes']['class']) && !empty($item['#wrapper_attributes']['class'])) ? $properties['wrapperClass'] = implode(" ", $item['#wrapper_attributes']['class']) : "";
    (isset($item['#date_date_min']) && !is_null($item['#date_date_min'])) ? $properties['dateMin'] = $item['#date_date_min'] : NULL;
    (isset($item['#date_date_max']) && !is_null($item['#date_date_max'])) ? $properties['dateMax'] = $item['#date_date_max'] : NULL;
    (isset($item['#min']) && !is_null($item['#min'])) ? $properties['attributes']['min'] = $item['#min'] : NULL;
    (isset($item['#max']) && !is_null($item['#max'])) ? $properties['attributes']['max'] = $item['#max'] : NULL;
    (isset($item['#step']) && !is_null($item['#step'])) ? $properties['attributes']['step'] = $item['#step'] : NULL;

    // add custom properties
    if (isset($item['#attributes']) && is_array($item['#attributes']) ) {
      foreach ($item['#attributes'] as $key => $value) {
        $properties['attributes'][$key] = $value;
      }
    }

    $properties['isMultiple'] = isset($item['#multiple']);
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
    // phpcs:enable
    if ($type === 'email' || $type === 'webform_email_confirm') {
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

    if ($type === 'webform_term_select' || $type === 'webform_term_checkboxes') {
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

    if (isset($properties['emptyOption']) && !empty($properties['emptyOption']) && !empty($properties['options'])) {
      $emptyOption = [
        'label' => $properties['emptyOption'],
        'value' => '',
      ];

      if (isset($properties['emptyValue']) && !empty($properties['emptyValue'])) {
        $emptyOption['value'] = $properties['emptyValue'];
      }

      array_unshift($properties['options'], $emptyOption);
    }

    if ($type === 'captcha') {
      $properties['validation']['required'] = TRUE;
      $properties['captcha_type'] = $item['#captcha_type'];
    }

    if ($ui_type === 'upload') {
      $element = $this->webform->getElement($field_name);
      $webform_submission = WebformSubmission::create([
        'webform_id' => $this->webform->id(),
      ]);
      // Prepare upload location and validators for the element.
      $element_plugin = $this->getElementPlugin($element);
      $element_plugin->prepare($element, $webform_submission);

      if (isset($item['#multiple']) && is_int($item['#multiple'])) {
        $properties['validation']['maxFiles'] = $item['#multiple'];
      }

      if (isset($item['#max_filesize'])) {
        $properties['validation']['maxSizeBytes'] = 1024 * 1024 * intval($item['#max_filesize']);
        $properties['maxSizeMb'] = intval($item['#max_filesize']);
      }

      $properties['filePreview'] = isset($item['#file_preview']);
      $fid = $properties['default_value'];
      if (is_numeric($fid)) {
        $properties['default_value'] = $this->preparePreviewInfos($fid);
      }

      if (isset($element['#upload_validators']) && isset($element['#upload_validators']['file_validate_extensions'][0])) {
        $field_extensions = $element['#upload_validators']['file_validate_extensions'][0];
        $extensions = explode(" ", $field_extensions);
        $doted_extensions = [];
        foreach ($extensions as $ext) {
          array_push($doted_extensions, "." . $ext);
        }
        $filenamed_extensions = implode(",", $doted_extensions);
        $properties['validation']['extensions'] = $filenamed_extensions;
        $properties['extensionsClean'] = $field_extensions;
      }

    }

    if ($ui_type === 'rawhtml') {
      $properties['html'] = $item['#text'];
      $properties['format'] = $item['#format'];
      $properties['attributes'] = $item['#wrapper_attributes'] ?? [];
    }

    if ($ui_type === 'range') {
      (isset($item['#output'])) ? $properties['output'] = $item['#output'] : NULL;
    }

    if (isset($item['#states'])) {
      $properties['states'] = $this->getFormElementStates($item);
    }

    // Override default values when the query params include test.
    if ($this->checkIfWeShouldPrepareTestsValues()) {
      $this->prepareFormElementTestValue($field_name, $properties);
    }

    return $properties;
  }

  /**
   * Check if we should prepare tests values.
   */
  private function checkIfWeShouldPrepareTestsValues() {
    $query = \Drupal::request()->query->all("q");
    return !in_array($this->webform->id(), self::WEBFORM_TESTS_EXCLUDED) && isset($query["test"]);
  }

  /**
   * Prepare form element test value.
   */
  private function prepareFormElementTestValue($field_name, &$properties) {
    $parents = $this->findParents($field_name, $this->formWithTestsValues);
    $child = !empty($parents) ? NestedArray::getValue($this->formWithTestsValues, array_merge($parents, [$field_name])) : [];
    $concenedElement = !empty($child) && in_array($field_name, $child['#array_parents'] ?? []) ? $child : $this->formWithTestsValues[$field_name];
    $properties['default_value'] = $concenedElement['#default_value'] ?? '';
    if ($properties['type'] == 'upload') {
      $this->prepareUploadElementTestValue($properties);
    }
  }

  /**
   * Prepare upload element test value.
   */
  private function prepareUploadElementTestValue(&$properties) {
    if (!$properties['isMultiple']) {
      $fid = is_array($properties['default_value']) ? array_values($properties['default_value'])[0] : NULL;
      if (!is_null($fid)) {
        $properties['default_value'] = $this->preparePreviewInfos($fid);
        return;
      }
    }
    $files = [];
    foreach ($properties['default_value'] as $fid) {
      $fileInfo = $this->preparePreviewInfos($fid);
      if (!empty($fileInfo)) {
        $files[] = $fileInfo;
      }
    }
    $properties['default_value'] = $files;
  }

  /**
   * Prepare preview infos.
   */
  private function preparePreviewInfos($fid) {
    $file = File::load($fid);
    if (!isset($file)) {
      return [];
    }
    $privateFile = $this->vactoryPrivateFilesService->generatePrivateFileUrl($file);
    return [
      'fid'        => $fid,
      'size'       => $file->get('filesize')->value,
      'type'       => $file->get('filemime')->value,
      'name'       => $file->get('filename')->value,
      'previewUrl' => $privateFile['_default'] ?? $file->createFileUrl(TRUE),
    ];
  }

  /**
   * Default value tokens replace.
   */
  private function defaultValueTokensReplace($item, $field_name, $default_value_key = '#default_value') {
    $default_value = $this->webformTokenManager->replace($this->defaultValues[$field_name] ?? $item[$default_value_key], NULL, [], []);
    if (isset($item['#multiple']) && isset($default_value[0])) {
      $default_value = explode(',', $default_value[0]);
    }

    // Html special chars decode when exists.
    if (is_array($default_value)) {
      $default_value = array_map(function ($value) {
        return htmlspecialchars_decode($value);
      }, $default_value);
    }
    if (is_string($default_value)) {
      $default_value = htmlspecialchars_decode($default_value);
    }
    return $default_value;
  }

  /**
   * Returns form element states.
   */
  private function getFormElementStates($item) {
    $states = [];
    foreach ($item['#states'] as $state => $conditions) {
      $operator = 'and';
      $conditions_to_append = [];
      $operator_exists = FALSE;
      foreach ($conditions as $key => $condition) {
        $item = [];
        if (in_array('or', $conditions) || in_array('xor', $conditions)) {
          $operator_exists = TRUE;
          if ($condition == 'or' || $condition == 'xor') {
            $operator = $condition;
            continue;
          }
          $selector = array_keys($condition)[0];
        }
        else {
          $selector = $key;
        }

        $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
        if (!$input_name) {
          continue;
        }
        $element_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 0);
        $item['element'] = $element_key;
        $item['operator'] = $operator_exists ? array_keys($condition[$selector])[0] : array_keys($condition)[0];
        $item['value'] = $operator_exists ? $condition[$selector][$item['operator']] : $condition[$item['operator']];
        array_push($conditions_to_append, $item);
      }
      $states[$state]['operator'] = $operator;
      $states[$state]['checks'] = $conditions_to_append;
    }
    return $states;
  }

  /**
   * Format options.
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
   *   Element interface.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getElementPlugin(array $element) {
    /** @var \Drupal\Core\Render\ElementInfoManager $plugin_manager */
    $plugin_definition = $this->webformElementManager->getDefinition($element['#type']);

    $element_plugin = $this->webformElementManager->createInstance($element['#type'], $plugin_definition);

    return $element_plugin;
  }

  /**
   * Find the parent elements of a specified key within an array.
   */
  protected function findParents($key, $array, $parentKey = NULL, &$parents = []) {
    foreach ($array as $currentKey => $value) {
      if ($currentKey === $key) {
        // If current key matches target key, add the parent to the list.
        if ($parentKey !== NULL && !str_starts_with($parentKey, '#')) {
          $parents[] = $parentKey;
        }
        break;
      }

      if (is_array($value)) {
        // Recursively search in current sub-array
        // with current key as the parent.
        $this->findParents($key, $value, $currentKey, $parents);
      }
    }
    return $parents;
  }

}
