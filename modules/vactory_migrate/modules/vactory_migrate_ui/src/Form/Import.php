<?php

namespace Drupal\vactory_migrate_ui\Form;


use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use League\Csv\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Migration import form.
 */
class Import extends ConfirmFormBase {

  /**
   *
   * @var \Drupal\vactory_migrate\Services\Rollback
   *
   */
  protected $rollbackService;

  protected $step = 1;

  protected $type;

  protected $migration_id;

  protected $csv;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->rollbackService = $container->get('vactory_migrate.rollback');
    return $instance;
  }


  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_migrate_ui.import';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
    ];

    $form['container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'csv-container'],
    ];

    $value = $form_state->getValue('migration');
    if ($value !== NULL || isset($this->migration_id)) {
      $form['container']['csv'] = [
        '#type'              => 'managed_file',
        '#title'             => $this->t('CSV file'),
        '#name'              => 'csv',
        '#upload_location'   => 'private://migrate-tmp',
        '#upload_validators' => [
          'file_validate_extensions' => ['csv'],
        ],
        '#description'       => t("Load the csv file to import.<br>" . $message),
        '#required'          => TRUE,
      ];
      $form['container']['type'] = [
        '#type'        => 'radios',
        '#title'       => $this->t("Type d'import"),
        '#options'     => [
          'diff' => $this->t('Diff'),
          'full' => $this->t('Full'),
        ],
        '#description' => t("<b>Diff</b> : Select this option if you want to import a differential file.<br><b>Full</b> : Choose this option if you want to completely replace the existing data.<br>"),
        '#required'    => TRUE,
      ];

      $form['container']['submit'] = [
        '#type'        => 'submit',
        '#value'       => $this->t("Start process"),
        '#button_type' => 'primary',
      ];
    }


    return $form;
  }

  public function promptCallback($form, FormStateInterface $form_state) {
    return $form['container'];
  }


  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->step === 2) {
      return;
    }
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement['#name'] == 'csv_remove_button') {
      return;
    }
    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
    //Check if header is correct
    $migration_id = $form_state->getValue('migration');
    $csv = $form_state->getValue('csv');
    $this->migration_id = $migration_id;
    $this->csv = $csv;
    // Validation de header
    if (isset($csv)) {
      $source = $this->getMigrationSource($migration_id);
      $original_path = $source['path'];
      $header = $this->getCSVHeader($original_path, $source['delimiter']);
      $fid = (int) reset($csv);
      $file = File::load($fid);
      $file_path = NULL;
      if ($file) {
        $file_path = \Drupal::service('file_system')
          ->realpath($file->getFileUri());
      }
      if (!empty($header)) {
        $new_header = $this->getCSVHeader($file_path, $delimiter);
        $compare_headers = array_diff($header, $new_header);
        if (!empty($compare_headers)) {
          $form_state->setErrorByName('csv', $this->t('The CSV file has an incorrect header.'));
        }
      }

      $check_content = $this->isValidCsvContent($file_path, $delimiter, count($header));
      if (!$check_content['status']) {
        $form_state->setErrorByName('csv', $this->t('Invalid CSV content format at line ' . $check_content['line']));
      }
      $id = $this->getMigrationId($migration_id);
      if (count($id) != 1) {
        $form_state->setErrorByName('csv', $this->t('Migration should have only one id field'));
      }
      else {
        $check_duplicated_id = $this->isColumnDuplicated($file_path, $delimiter, reset($id));
        if (!$check_duplicated_id['status']) {
          $form_state->setErrorByName('csv', $this->t('CSV contains duplicated ID : ' . $check_duplicated_id['value']));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
    if ($this->step === 1) {
      $type = $form_state->getValue('type');
      $migration_id = $form_state->getValue('migration');
      $csv = $form_state->getValue('csv');
      $form_state->setRebuild();
      $this->step = 2;
      $this->migration_id = $migration_id;
      $this->type = $type;
      $this->csv = $csv;

      return;
    }
    $type = $this->type;
    $migration_id = $this->migration_id;
    $csv = $this->csv;

    //Get new file
    $fid = (int) reset($csv);
    $new_file = File::load($fid);
    $new_file_path = \Drupal::service('file_system')
      ->realpath($new_file->getFileUri());
    $source = $this->getMigrationSource($migration_id);
    $original_file_path = $source['path'];

    //prepare new file
    if ($type == 'diff') {
      $this->appendCSV($new_file_path, $original_file_path, $delimiter);
    }
    if ($type == 'full') {
      $this->replaceCSV($new_file_path, $original_file_path);
    }

    //Lancer rollback
    $pieces = explode('.', $migration_id);
    $id = end($pieces);

    $this->rollbackService->rollback($id);
    $url = Url::fromRoute('vactory_migrate_ui.import_confirmation')
      ->setRouteParameters(['migration' => $id]);

    $form_state->setRedirectUrl($url);
    //todo remove new files

    $new_file->delete();
  }

  private function getMigrationsList() {
    $migration_configs = \Drupal::configFactory()
      ->listAll('migrate_plus.migration.');
    $migrations = [];
    foreach ($migration_configs as $migration_config) {
      $config = \Drupal::configFactory()->get($migration_config);
      $source = $config->get('source');
      if (isset($source) && key_exists('plugin', $source)) {
        if ($source['plugin'] == 'csv') {
          $migrations[$migration_config] = $config->get('label');
        }
      }
    }
    return $migrations;
  }


  function isValidCsvContent($path, $delimiter, $expected_columns) {
    $index = 0;
    $handle = fopen($path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      // Check if the row has the expected number of columns
      if (count($row) != $expected_columns) {
        return ['status' => FALSE, 'line' => $index + 1];
      }
      $index++;
    }

    fclose($handle);
    return ['status' => TRUE];
  }


  private function getCSVHeader($path, $delimiter) {
    $csv = fopen($path, 'r');
    if ($csv) {
      $header = fgetcsv($csv, NULL, $delimiter);
      return $header;
    }
    return [];
  }

  private function getMigrationSource($migration_id) {
    $migration_config = \Drupal::configFactory()->get($migration_id);
    $source = $migration_config->get('source');
    return $source;
  }

  private function getMigrationId($migration_id) {
    $migration_config = \Drupal::configFactory()->get($migration_id);
    $source = $migration_config->get('source');
    return $source['ids'];
  }

  private function appendCSV($source, $destination, $delimiter) {
    $sourceHandle = fopen($source, 'r');
    $destinationHandle = fopen($destination, 'a');
    $header = fgetcsv($sourceHandle, NULL, $delimiter);
    fseek($destinationHandle, 0, SEEK_END);
    fwrite($destinationHandle, PHP_EOL);
    while (($data = fgetcsv($sourceHandle, NULL, $delimiter)) !== FALSE) {
      fputcsv($destinationHandle, $data, $delimiter);
    }
    fclose($sourceHandle);
    fclose($destinationHandle);
  }

  private function replaceCSV($source, $destination) {
    if (file_exists($source)) {
      unlink($destination);
      rename($source, $destination);
    }
  }

  private function isColumnDuplicated($file_path, $delimiter, $column_name) {
    $handle = fopen($file_path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    $header = fgetcsv($handle, 0, $delimiter);
    $column_index = array_search($column_name, $header);
    if ($column_index === FALSE) {
      fclose($handle);
      return FALSE; // Column name not found
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
          ]; // Column value is duplicated
        }
        $values[] = $value;
      }
    }

    fclose($handle);
    return ['status' => TRUE]; // Column value is not duplicated
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
