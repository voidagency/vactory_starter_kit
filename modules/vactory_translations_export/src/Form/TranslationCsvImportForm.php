<?php

namespace Drupal\vactory_translations_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Vactory translation csv import form.
 */
class TranslationCsvImportForm extends FormBase {

  /**
   * Interface translation manager service.
   *
   * @var \Drupal\vactory_translations_export\Services\TranslationExportManager
   */
  protected $interfaceTranslationManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->interfaceTranslationManager = $container->get('vactory_translations_export.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_translations_csv_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('vactory_translations_export.settings');
    $form['delimiter'] = [
      '#type' => 'select',
      '#title' => $this->t('CSV Delimiter'),
      '#options' => [
        ',' => $this->t('Comma (,)'),
        ';' => $this->t('Semicolon (;)'),
      ],
      '#default_value' => $config->get('delimiter') ?? ',',
    ];
    $form['contexts'] = [
      '#type' => 'details',
      '#title' => $this->t('Concerned Contexts'),
    ];

    $form['contexts']['context'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Concerned contexts'),
      '#options' => $this->interfaceTranslationManager->getTranslationContexts(),
      '#default_value' => $config->get('context') ?? ['_FRONTEND'],
      '#description' => $this->t('When no context has been selected then all contexts are concerned'),
    ];
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV file'),
      '#name' => 'csv_file',
      '#upload_location' => 'private://vactory-translations-import-tmp',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => t("Load translations csv file."),
      '#required' => TRUE,
    ];

    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $delimiter = $values['delimiter'];
    $context = $values['context'] ?? [];
    $context = array_filter($context, fn($el) => $el !== 0);
    $fid = (int) reset($values['csv_file']);
    $file = File::load($fid);
    if ($file) {
      $file_path = $file->getFileUri();
      if ($file_path) {
        $languages = \Drupal::languageManager()->getLanguages();
        $langcodes = array_keys($languages);
        $path = \Drupal::service('file_system')->realpath($file_path);
        $csv_data = $this->getCsvContent($path, $delimiter);
        $chunk = array_chunk($csv_data, 5);
        foreach ($chunk as $data) {
          $operations[] = [
            [$this, 'updateTranslationsCallback'],
            [$data, $langcodes, $context],
          ];
        }

        if (!empty($operations)) {
          $batch = [
            'title' => 'Process of updating interface translations strings',
            'operations' => $operations,
            'finished' => [$this, 'updateTranslationsFinished'],
          ];
          batch_set($batch);
        }

      }
    }
  }

  /**
   * Update translations batch callback.
   */
  public function updateTranslationsCallback($csv_data, $langcodes, $translation_context, &$context) {
    foreach ($csv_data as $data) {
      $source = $data['source'] ?? NULL;
      if (empty($source)) {
        continue;
      }

      foreach ($langcodes as $langcode) {
        $key = strtoupper($langcode) . " translation";
        if (!isset($data[$key])) {
          continue;
        }
        $translation = $data[$key];
        $this->interfaceTranslationManager->updateTranslation($source, $translation, $langcode, $translation_context);
      }
    }
    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($csv_data);
  }

  /**
   * Update translations finished.
   */
  public static function updateTranslationsFinished($success, $results, $operations) {
    if ($success) {
      drupal_flush_all_caches();
      $message = $results['count'] > 0 ? "{$results['count']} interface translations has been updated successfully." : "Nothing to update!";
      \Drupal::messenger()->addStatus($message);
    }
  }

  /**
   * Transform array to csv file.
   */
  private function getCsvContent($path, $delimiter) {
    $csv = fopen($path, 'r');
    // Initialize an array to store all the rows.
    $data = [];
    if ($csv) {
      // Get the csv header.
      $header = fgetcsv($csv, NULL, $delimiter);

      while (($row = fgetcsv($csv, NULL, $delimiter)) !== FALSE) {
        // Combine the header and row into an associative array.
        $rowData = array_combine($header, $row);

        // Add the row data to the array.
        $data[] = $rowData;
      }

      fclose($csv);
    }
    return $data;
  }

}
