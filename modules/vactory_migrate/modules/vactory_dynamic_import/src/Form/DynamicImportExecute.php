<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Markup;

/**
 * Migration import form.
 */
class DynamicImportExecute extends ConfirmFormBase
{

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
    protected $migrationId = null;

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
     * The term normalization service.
     *
     * @var \Drupal\vactory_dynamic_import\Service\TermNormalizationService
     */
    protected $termNormalization;

    /**
     * {@inheritDoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->rollbackService = $container->get('vactory_migrate.rollback');
        $instance->entityInfo = $container->get('vactory_migrate.entity_info');
        $instance->termNormalization = $container->get('vactory_dynamic_import.term_normalization');
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'vactory_migrate_ui.import';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $params = \Drupal::request()->query->all();
        if (array_key_exists('id', $params)) {
            $this->migrationId = $params['id'];
        }

        if ($this->step === 2) {
            return parent::buildForm($form, $form_state);
        }

        $url_options = ['absolute' => true];
        $t_args = [
            ':settings_url' => Url::fromUri('base:/admin/structure/file-types/manage/document/edit', $url_options)
                ->toString(),
        ];
        $message = t('If you\'re having trouble uploading the csv file. Add <strong><em>text/csv</em></strong> <a target="_blank" href=":settings_url"> to the allowed <em>MIME types</em></a>.', $t_args);

        $form['migration'] = [
            '#type' => 'select',
            '#title' => $this->t('Migration'),
            '#options' => $this->getMigrationsList(),
            '#empty_option' => $this->t("-- Choose import --"),
            '#description' => t("Choose the import to perform."),
            '#required' => true,
            '#ajax' => [
                'callback' => '::promptCallback',
                'wrapper' => 'csv-container',
            ],
            '#default_value' => !is_null($this->migrationId) ? $this->migrationId : '',
            '#disabled' => !is_null($this->migrationId),
        ];

        $form['container'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'csv-container'],
        ];

        $value = $form_state->getValue('migration');
        if ($value !== null || isset($this->migrationId)) {
            $form['container']['csv'] = [
                '#type' => 'managed_file',
                '#title' => $this->t('CSV file'),
                '#name' => 'csv',
                '#upload_location' => 'private://migrate-tmp',
                '#upload_validators' => [
                    'file_validate_extensions' => ['csv'],
                ],
                '#description' => t("Load the csv file to import.<br>") . $message,
                '#required' => true,
            ];
            $form['container']['type'] = [
                '#type' => 'radios',
                '#title' => $this->t("Strategy"),
                '#options' => [
                    'rollback' => $this->t('Replace existing data associated with this migration (Rollback)'),
                    'full' => $this->t('Completely replace the existing data (all existing nodes of the same bundle).'),
                ],
                '#required' => true,
                '#default_value' => 'rollback',
            ];

            $form['container']['submit'] = [
                '#type' => 'submit',
                '#value' => $this->t("Start process"),
                '#button_type' => 'primary',
            ];
        }

        return $form;
    }

    /**
     * Ajax callback.
     */
    public function promptCallback($form, FormStateInterface $form_state)
    {
        return $form['container'];
    }

    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($this->step === 2) {
            return;
        }
        $triggeringElement = $form_state->getTriggeringElement();
        if ($triggeringElement['#name'] == 'csv_remove_button') {
            return;
        }

        $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
        $migration_id = $form_state->getValue('migration');
        $csv = $form_state->getValue('csv');

        // Validate that a file was uploaded
        if (empty($csv)) {
            $form_state->setErrorByName('csv', $this->t('Please upload a CSV file.'));
            return;
        }

        $this->migrationId = $migration_id;
        $this->csv = $csv;

        // Validate file exists and is readable
        $fid = (int) reset($csv);
        $file = File::load($fid);
        if (!$file) {
            $form_state->setErrorByName('csv', $this->t('Unable to load the uploaded file.'));
            return;
        }

        $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
        if (!$file_path || !file_exists($file_path) || !is_readable($file_path)) {
            $form_state->setErrorByName('csv', $this->t('Unable to read the uploaded file.'));
            return;
        }

        // Now proceed with CSV validation
        $this->trimCsvHeader($file_path, $delimiter);
        $header = $this->getCsvHeader($file_path, $delimiter);

        if (empty($header)) {
            $form_state->setErrorByName('csv', $this->t('The CSV file appears to be empty or malformed.'));
            return;
        }

        // Add term normalization validation.
        $term_validation = $this->termNormalization->validateTerms($file_path, $header, $delimiter);
        if (!$term_validation['status']) {
            $error_message = $this->t('Term normalization issues found:') . '<br/><br/>';
            foreach ($term_validation['errors'] as $error) {
                $error_message .= $error . '<br/><br/>';
            }
            $form_state->setErrorByName('csv', Markup::create($error_message));
            return;
        } elseif (!empty($term_validation['term_fields'])) {
            \Drupal::messenger()->addStatus($this->t('Term validation passed successfully.'));
        }

        $check_content = $this->isValidCsvContent($file_path, $delimiter, count($header));
        if (!$check_content['status']) {
            $form_state->setErrorByName('csv', $this->t('Invalid CSV content format at line') . ' ' . $check_content['line']);
            return;
        }

        $id = $this->getMigrationId($migration_id);
        if (count($id) != 1) {
            $form_state->setErrorByName('csv', $this->t('Migration should have only one id field'));
            return;
        }

        $check_duplicated_id = $this->isColumnDuplicated($file_path, $delimiter, reset($id));
        if (!$check_duplicated_id['status']) {
            $form_state->setErrorByName('csv', $this->t('CSV contains duplicated ID :') . ' ' . $check_duplicated_id['value']);
            return;
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
        $batch_size = \Drupal::config('vactory_migrate.settings')->get('batch_size');
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
        $mapping_table = 'migrate_map_' . $id;
        $message_table = 'migrate_message_' . $id;

        if ($type == 'rollback') {
            $this->rollbackService->rollback($id);
        } elseif ($type == 'full') {
            $destination = $this->entityInfo->getDestinationByMigrationId($migration_id);
            $entity_type = $destination['entity'];
            $bundle = $destination['bundle'];

            // Delete migration tables directly.
            $database = \Drupal::database();
            if ($database->schema()->tableExists($mapping_table)) {
                $database->schema()->dropTable($mapping_table);
            }
            if ($database->schema()->tableExists($message_table)) {
                $database->schema()->dropTable($message_table);
            }

            // Create batch for deleting all nodes of the bundle.
            $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
            $entity_type_definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
            $bundle_field = $entity_type_definition->getKey('bundle');

            // We delete all entities of this bundle regardless of lang.
            $query = $entity_storage->getQuery()
                ->accessCheck(false)
                ->condition($bundle_field, $bundle);

            $entity_ids = $query->execute();

            if (!empty($entity_ids)) {
                $chunks = array_chunk($entity_ids, $batch_size);
                $operations = [];

                foreach ($chunks as $chunk) {
                    $operations[] = [
                        [$this, 'deleteEntitiesBatch'],
                        [$chunk, $entity_type, null],
                    ];
                }

                $batch = [
                    'title' => t('Deleting all existing content of this type...'),
                    'operations' => $operations,
                    'finished' => [$this, 'deleteEntitiesBatchFinished'],
                ];

                batch_set($batch);
            }
        }

        $url = Url::fromRoute('vactory_dynamic_import.confirmation')
            ->setRouteParameters(['migration' => $id]);

        $form_state->setRedirectUrl($url);
    }

    /**
     * Batch operation to delete entities.
     */
    public function deleteEntitiesBatch($ids, $entity_type, $langcode, &$context)
    {
        $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);

        foreach ($ids as $id) {
            $entity = $entity_storage->load($id);
            if ($entity) {
                // If langcode is NULL or it's the default language, delete the entire entity
                if ($langcode === null || $langcode === \Drupal::languageManager()->getDefaultLanguage()->getId()) {
                    $entity->delete();
                }
                // Otherwise, try to remove just the translation
                elseif ($entity->hasTranslation($langcode)) {
                    // Only try to remove the translation if:
                    // 1. It's not the default language translation
                    // 2. The entity has more than one translation (can't remove the last translation)
                    if ($langcode !== $entity->getUntranslated()->language()->getId() && count($entity->getTranslationLanguages()) > 1) {
                        $entity->removeTranslation($langcode);
                        $entity->save();
                    } else {
                        // If this is the only translation or it's the original language, delete the entire entity
                        $entity->delete();
                    }
                }
            }
        }

        if (!isset($context['results']['count'])) {
            $context['results']['count'] = 0;
        }
        $context['results']['count'] += count($ids);
    }

    /**
     * Batch finished callback for entity deletion.
     */
    public function deleteEntitiesBatchFinished($success, $results, $operations)
    {
        if ($success) {
            $count = $results['count'] ?? 0;
            \Drupal::messenger()->addStatus(t('Deleted @count entities.', ['@count' => $count]));
        } else {
            \Drupal::messenger()->addError(t('An error occurred while deleting entities.'));
        }
    }

    /**
     * Get migrations having csv as source plugin.
     */
    private function getMigrationsList()
    {
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
    private function isValidCsvContent($path, $delimiter, $expected_columns)
    {
        $index = 0;
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return false;
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Check if the row has the expected number of columns.
            if (count($row) != $expected_columns) {
                return ['status' => false, 'line' => $index + 1];
            }
            $index++;
        }

        fclose($handle);
        return ['status' => true];
    }

    /**
     * Get csv file header.
     */
    private function getCsvHeader($path, $delimiter)
    {
        $csv = fopen($path, 'r');
        if ($csv) {
            $header = fgetcsv($csv, null, $delimiter);
            return $header;
        }
        return [];
    }

    /**
     * Get migration id column.
     */
    private function getMigrationId($migration_id)
    {
        $migration_config = \Drupal::configFactory()->get($migration_id);
        $source = $migration_config->get('source');
        return $source['ids'];
    }

    /**
     * Check duplicated lines.
     */
    private function isColumnDuplicated($file_path, $delimiter, $column_name)
    {
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return false;
        }

        $header = fgetcsv($handle, 0, $delimiter);
        $column_index = array_search($column_name, $header);
        if ($column_index === false) {
            fclose($handle);
            return false;
        }

        $values = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (isset($row[$column_index])) {
                $value = $row[$column_index];
                if (in_array($value, $values)) {
                    fclose($handle);
                    return [
                        'status' => false,
                        'value' => $value,
                    ];
                }
                $values[] = $value;
            }
        }

        fclose($handle);
        return ['status' => true];
    }

    /**
     * Returns the question to ask the user.
     *
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup
     *   The form question. The page title will be set to this value.
     */
    public function getQuestion()
    {
        return $this->t('Do you want to rollback');
    }

    /**
     * Returns the route to go to if the user cancels the action.
     *
     * @return \Drupal\Core\Url
     *   A URL object.
     */
    public function getCancelUrl()
    {
        return new Url('vactory_migrate_ui.import');
    }

    /**
     * Helper function to trim the header of the CSV file.
     */
    protected function trimCsvHeader($file_uri, $delimiter = ',')
    {
        $trimmed_data = [];
        if (($handle = fopen($file_uri, 'r+')) !== false) {
            $header = fgetcsv($handle, null, $delimiter);
            if ($header) {
                // Trim each header field.
                $trimmed_header = array_map('trim', $header);
                $trimmed_data[] = $trimmed_header;

                // Get the rest of the file content.
                while (($data = fgetcsv($handle, null, $delimiter)) !== false) {
                    $trimmed_data[] = $data;
                }

                // Rewrite the trimmed data back to the file.
                rewind($handle);
                foreach ($trimmed_data as $row) {
                    fputcsv($handle, $row);
                }
                // Truncate the file to remove any extra content from the original.
                ftruncate($handle, ftell($handle));
            }
            fclose($handle);
        }
    }

}
