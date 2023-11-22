<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class GenerateModelForm.
 *
 * Provides a form to export csv file model by content type.
 */
class GenerateModelForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Submitted values.
   *
   * @var array
   */
  protected $submitted = [];

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'dynamic_import_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, fn($entity_type) => $entity_type instanceof ContentEntityType);
    $entity_types = array_map(fn($entity_type) => $entity_type->getLabel(), $entity_types);
    $form['entity_type'] = [
      '#type'         => 'select',
      '#title'        => $this->t('Targeted entity type'),
      '#options'      => $entity_types,
      '#empty_option' => '- Select -',
      '#required'     => TRUE,
      '#ajax'         => [
        'callback' => '::bundlesCallback',
        'wrapper'  => 'bundles-container',
      ],
      '#description'  => $this->t('Select the destination content type'),
    ];

    $form['container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'bundles-container'],
    ];

    if (isset($this->submitted['entity_type']) && !empty($this->submitted['entity_type'])) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->submitted['entity_type']);
      $bundles = array_map(fn($bundle) => $bundle['label'], $bundles);
      $form['container']['bundle'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Targeted bundle'),
        '#options'      => $bundles,
        '#empty_option' => '- Select -',
        '#required'     => TRUE,
        '#ajax'         => [
          'callback' => '::bundlesCallback',
          'wrapper'  => 'bundles-container',
        ],
        '#description'  => $this->t('Select the targeted bundle'),
      ];

      if (isset($this->submitted['bundle']) && !empty($this->submitted['bundle'])) {
        $form['container']['delimiter'] = [
          '#type'        => 'textfield',
          '#title'       => $this->t('Delimiter'),
          '#required'    => TRUE,
          '#description' => $this->t('Enter the delimiter used in the CSV file.'),
        ];

        $form['container']['translation'] = [
          '#type'        => 'checkbox',
          '#title'       => $this->t('This is a translation'),
          '#description' => $this->t("For translations of existing content, please check this checkbox."),
        ];

        $form['container']['fields'] = [
          '#type'    => 'checkboxes',
          '#title'   => t('Concerned fields'),
          '#options' => $this->getRelatedFields($this->submitted['entity_type'], $this->submitted['bundle'], TRUE),
        ];

        $form['container']['submit'] = [
          '#type'        => 'submit',
          '#value'       => $this->t("Start process"),
          '#button_type' => 'primary',
        ];

      }

    }
    return $form;
  }

  /**
   * Ajax Callback.
   */
  public function bundlesCallback($form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $header = [
      'id',
    ];

    if ($values['translation']) {
      $header[] = 'original';
    }

    $fields = array_filter($values['fields'], function ($item) {
      return $item != 0;
    });

    $otiginal_fields = $this->getRelatedFields($values['entity_type'], $values['bundle']);

    foreach ($fields as $field) {
      $formatted_field = str_replace('/', ':', $field);
      $original = $otiginal_fields[$field];

      if ($original['type'] == 'date') {
        $header[] = 'date|' . $formatted_field . '|Y-m-d';
      }
      elseif ($original['type'] == 'file') {
        $header[] = 'file|' . $formatted_field . '|-';
      }
      elseif ($original['type'] == 'taxonomy_term') {
        $header[] = 'term|' . $formatted_field . '|+';
      }
      elseif (str_starts_with($original['type'], 'media')) {
        $bundle = explode(':', $original['type']);
        $header[] = 'media|' . $formatted_field . '|' . $bundle[1];
      }
      else {
        $header[] = '-|' . $formatted_field . '|-';
      }
    }

    $path = $this->generateCsv($header, [], "{$values['entity_type']}-{$values['bundle']}", $values['delimiter']);

    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($path), 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "{$values['entity_type']}-{$values['bundle']}" . '.csv');
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getTriggeringElement()['#name'];
    $this->submitted[$name] = $form_state->getValue($name);
    parent::validateForm($form, $form_state);
  }

  /**
   * Transform array to csv file.
   */
  private function generateCsv($header, $data, $filename, $delimiter) {
    $time = time();

    $destination = 'public://dynamic-import-model';
    if (!file_exists($destination)) {
      mkdir($destination, 0777);
    }
    $path = "{$destination}/{$filename}-{$time}.csv";
    $fp = fopen($path, 'w');
    fputcsv($fp, $header, $delimiter);
    // Loop through file pointer and a line.
    foreach ($data as $item) {
      fputcsv($fp, $item);
    }

    fclose($fp);
    return $path;
  }

  /**
   * Get field of a given entity and bundle.
   */
  private function getRelatedFields($entity_type, $bundle, $only_keys = FALSE) {

    $excluded_fields = [
      'revision_',
      'field_content_access',
      'vcc_',
      'internal_',
      'notification_',
      'mail_',
      'comment',
      'field_vactory_meta_tags',
      'field_vactory_seo_status',
    ];

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $fields = [];
    foreach ($field_definitions as $field_name => $field_definition) {

      if (!$this->startsWithAnyInArray($field_name, $excluded_fields)) {
        $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

        $field_label = !empty($field_definition->getLabel()) ? $field_definition->getLabel() : $field_name;
        $field_type = !empty($field_definition->getType()) ? $field_definition->getType() : '';

        if ($field_type == 'datetime') {
          $field_type = 'date';
        }
        if ($field_type == 'entity_reference') {
          $settings = $field_definition->getSettings();
          if (isset($settings['target_type']) && $settings['target_type'] == 'media') {
            if (isset($settings['handler_settings']['target_bundles']) && !is_null($settings['handler_settings']['target_bundles'])) {
              $field_type = 'media:' . reset($settings['handler_settings']['target_bundles']);
            }
          }
          if (isset($settings['target_type']) && $settings['target_type'] == 'taxonomy_term') {
            $field_type = 'taxonomy_term';
          }
        }
        if (!$field_storage) {
          $fields[$field_name] = $only_keys ? $field_name : [
            'label' => $field_label,
            'type'  => $field_type,
          ];
          continue;
        }

        $field_properties = $field_storage->getPropertyDefinitions();
        if (count($field_properties) === 1 || isset($field_properties['target_id'])) {
          $fields[$field_name] = $only_keys ? $field_name : [
            'label' => $field_label,
            'type'  => $field_type,
          ];
          continue;
        }
        foreach ($field_properties as $key => $field_property) {
          $fields["{$field_name}/{$key}"] = $only_keys ? "{$field_name}/{$key}" : [
            'label' => "{$field_label}/{$key}",
            'type'  => $field_type,
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * Check if array contains a value that starts with haystack.
   */
  private function startsWithAnyInArray($haystack, $array) {
    foreach ($array as $prefix) {
      if (strpos($haystack, $prefix) === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
