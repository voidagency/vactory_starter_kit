<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration import form.
 */
class DynamicImportExecute extends ConfirmFormBase {

  /**
   * Rollback service.
   *
   * @var \Drupal\vactory_migrate\Services\Rollback
   */
  protected $rollbackService;

  /**
   * Form step.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * Import type (strategy).
   *
   * @var string
   */
  protected $type;

  /**
   * Migration to be processed.
   *
   * @var string
   */
  protected $migrationId = NULL;

  /**
   * Source file.
   *
   * @var string
   */
  protected $csv;

  /**
   * Migrate entity info.
   *
   * @var \Drupal\vactory_migrate\Services\EntityInfo
   */
  protected $entityInfo;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->rollbackService = $container->get('vactory_migrate.rollback');
    $instance->entityInfo = $container->get('vactory_migrate.entity_info');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_migrate_ui.import';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = \Drupal::request()->query->all();
    if (array_key_exists('id', $params)) {
      $this->migrationId = $params['id'];
    }

    if ($this->step === 2) {
      return parent::buildForm($form, $form_state);
    }

    $url_options = ['absolute' => TRUE];
    $t_args = [
      ':settings_url' => Url::fromUri('base:/admin/structure/file-types/manage/document/edit', $url_options)
        ->toString(),
    ];
    $message = t('If you\'re having trouble uploading the csv file. Add <strong><em>text/csv</em></strong> <a target="_blank" href=":settings_url"> to the allowed <em>MIME types</em></a>.', $t_args);

    $form['migration'] = [
      '#type'         => 'select',
      '#title'        => $this->t('Migration'),
      '#options'      => $this->getMigrationsList(),
      '#empty_option' => $this->t("-- Choose import --"),
      '#description'  => t("Choose the import to perform."),
      '#required'     => TRUE,
      '#ajax'         => [
        'callback' => '::promptCallback',
        'wrapper'  => 'csv-container',
      ],
      '#default_value' => !is_null($this->migrationId) ? $this->migrationId : '',
      '#disabled' => !is_null($this->migrationId),
    ];

    $form['container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'csv-container'],
    ];

    $value = $form_state->getValue('migration');
    if ($value !== NULL || isset($this->migrationId)) {
      $form['container']['csv'] = [
        '#type'              => 'managed_file',
        '#title'             => $this->t('CSV file'),
        '#name'              => 'csv',
        '#upload_location'   => 'private://migrate-tmp',
        '#upload_validators' => [
          'file_validate_extensions' => ['csv'],
        ],
        '#description'       => t("Load the csv file to import.<br>") . $message,
        '#required'          => TRUE,
      ];
      $form['container']['type'] = [
        '#type'        => 'radios',
        '#title'       => $this->t("Strategy"),
        '#options'     => [
          'rollback' => $this->t('Replace existing data associated with this migration (Rollback)'),
          'full' => $this->t('Completely replace the existing data (all existing nodes of the same bundle).'),
        ],
        '#required'    => TRUE,
        '#default_value' => 'rollback',
      ];

      $form['container']['submit'] = [
        '#type'        => 'submit',
        '#value'       => $this->t("Start process"),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function promptCallback($form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->step === 2) {
      return;
    }
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement['#name'] == 'csv_remove_button') {
      return;
    }
    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
    // Check if header is correct.
    $migration_id = $form_state->getValue('migration');
    $csv = $form_state->getValue('csv');
    $this->migrationId = $migration_id;
    $this->csv = $csv;
    // Validation de header.
    if (isset($csv)) {
      $fid = (int) reset($csv);
      $file = File::load($fid);
      $file_path = NULL;
      if ($file) {
        $file_path = \Drupal::service('file_system')
          ->realpath($file->getFileUri());
      }
      $header = $this->getCsvHeader($file_path, $delimiter);

      $check_content = $this->isValidCsvContent($file_path, $delimiter, count($header));
      if (!$check_content['status']) {
        $form_state->setErrorByName('csv', $this->t('Invalid CSV content format at line') . ' ' . $check_content['line']);
      }
      $id = $this->getMigrationId($migration_id);
      if (count($id) != 1) {
        $form_state->setErrorByName('csv', $this->t('Migration should have only one id field'));
      }
      else {
        $check_duplicated_id = $this->isColumnDuplicated($file_path, $delimiter, reset($id));
        if (!$check_duplicated_id['status']) {
          $form_state->setErrorByName('csv', $this->t('CSV contains duplicated ID :') . ' ' . $check_duplicated_id['value']);
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
    if ($this->step === 1) {
      $type = $form_state->getValue('type');
      $migration_id = $form_state->getValue('migration');
      $csv = $form_state->getValue('csv');
      $form_state->setRebuild();
      $this->step = 2;
      $this->migrationId = $migration_id;
      $this->type = $type;
      $this->csv = $csv;
      return;
    }
    $type = $this->type;
    $migration_id = $this->migrationId;
    $csv = $this->csv;

    // Get new file.
    $fid = (int) reset($csv);
    $new_file = File::load($fid);
    $new_file_path = \Drupal::service('file_system')
      ->realpath($new_file->getFileUri());
    $migration_config = \Drupal::configFactory()->getEditable($migration_id);
    $migration_config_data = $migration_config->getRawData();
    $migration_config_data['source']['path'] = $new_file_path;
    $migration_config->setData($migration_config_data);
    $migration_config->save();

    // Lancer rollback.
    $pieces = explode('.', $migration_id);
    $id = end($pieces);

    $this->rollbackService->rollback($id);

    if ($type == 'full') {
      $destination = $this->entityInfo->getDestinationByMigrationId($migration_id);
      $langcode = $destination['langcode'];
      $default_laguage = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $entity_type_definition = \Drupal::entityTypeManager()->getDefinition($destination['entity']);
      $bundle_field = $entity_type_definition->getKey('bundle');
      $entity_storage = \Drupal::entityTypeManager()->getStorage($destination['entity']);
      $entity_ids = $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($bundle_field, $destination['bundle'])
        ->condition('langcode', $langcode)
        ->execute();
      foreach ($entity_ids as $entity_id) {
        $entity = $entity_storage->load($entity_id);
        if ($langcode === $default_laguage) {
          $entity->delete();
        }
        elseif ($entity->hasTranslation($langcode)) {
          $entity->removeTranslation($langcode);
          $entity->save();
        }
      }
    }

    $url = Url::fromRoute('vactory_dynamic_import.confirmation')
      ->setRouteParameters(['migration' => $id]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * Get migrations having csv as source plugin.
   */
  private function getMigrationsList() {
    $migration_configs = \Drupal::configFactory()
      ->listAll('migrate_plus.migration.');
    $migrations = [];
    foreach ($migration_configs as $migration_config) {
      $config = \Drupal::configFactory()->get($migration_config);
      $source = $config->get('source');
      if (isset($source) && array_key_exists('plugin', $source)) {
        if ($source['plugin'] == 'csv') {
          $migrations[$migration_config] = $config->get('label');
        }
      }
    }
    return $migrations;
  }

  /**
   * Check if csv line structure.
   */
  private function isValidCsvContent($path, $delimiter, $expected_columns) {
    $index = 0;
    $handle = fopen($path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      // Check if the row has the expected number of columns.
      if (count($row) != $expected_columns) {
        return ['status' => FALSE, 'line' => $index + 1];
      }
      $index++;
    }

    fclose($handle);
    return ['status' => TRUE];
  }

  /**
   * Get csv file header.
   */
  private function getCsvHeader($path, $delimiter) {
    $csv = fopen($path, 'r');
    if ($csv) {
      $header = fgetcsv($csv, NULL, $delimiter);
      return $header;
    }
    return [];
  }

  /**
   * Get migration id column.
   */
  private function getMigrationId($migration_id) {
    $migration_config = \Drupal::configFactory()->get($migration_id);
    $source = $migration_config->get('source');
    return $source['ids'];
  }

  /**
   * Check duplicated lines.
   */
  private function isColumnDuplicated($file_path, $delimiter, $column_name) {
    $handle = fopen($file_path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    $header = fgetcsv($handle, 0, $delimiter);
    $column_index = array_search($column_name, $header);
    if ($column_index === FALSE) {
      fclose($handle);
      return FALSE;
    }

    $values = [];
    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      if (isset($row[$column_index])) {
        $value = $row[$column_index];
        if (in_array($value, $values)) {
          fclose($handle);
          return [
            'status' => FALSE,
            'value'  => $value,
          ];
        }
        $values[] = $value;
      }
    }

    fclose($handle);
    return ['status' => TRUE];
  }

  /**
   * Returns the question to ask the user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Do you want to rollback');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('vactory_migrate_ui.import');
  }

}
