<?php

namespace Drupal\vactory_extended_seo\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Importer\ConfigImporterBatch;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Provide settings form for static hreflang.
 */
class ImportFile extends ConfigFormBase {

  /**
   *
   */
  public const DELETE_ALL = -1;

  /**
   *
   */
  public const DELETE_LAST = 1;

  /**
   * @var array
   */
  public $imported_ids;

  /**
   * @var
   */
  protected $activeLanguages;

  /**
   * @var mixed
   */
  protected mixed $manager;

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_extended_seo_import.settings'];
  }

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->imported_ids = [];
    $this->activeLanguages = \Drupal::service('language_manager')->getLanguages();
    $this->manager = \Drupal::service('entity_type.manager');
  }


  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_extended_seo_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_instructions'] = [
      '#markup' => $this->t("<b>The mapping between the entity and the target node is based on a priority
                    computation :<br/> First we check if you provided the node id to be used if not then we perform
                    a lookup based on the title if neither of the two columns are mentioned we try to perform a lookup
                    based on the url column which must have a valid slug without the language prefix !</b> <br/>"),
    ];
    $form['file_data'] = [
      '#type' => 'managed_file',
      '#name' => 'avis_data',
      '#title' => $this->t('Hreflang mapping data (CSV)'),
      '#size' => 30,
      '#description' => '<b><a href="/modules/custom/vactory_extended_seo/artifacts/model.csv" >' .
        $this->t("CSV file example")  . '</b></a><br/>' .
        '<a href="/admin/structure/file-types/manage/document/edit" target="_blank">' .
        $this->t("Check if csv extension is enabled")  . '</a>',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [1 * 1024 * 1024],
      ],
      '#upload_location' => 'private://vactory_extended_seo/',
      '#default_value' => $this->config('vactory_extended_seo_import.settings')->get('file_data') ?: '',
    ];

    $form['import_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Batch import (especially for large dataset)'),
      '#size' => 10,
      '#maxlength' => 255,
      '#default_value' => 0,
    ];

    $form['batch_size'] = [
      '#type' => 'textfield',
      '#attributes' => array(
        ' type' => 'number',
      ),
      '#title' => $this->t('Batch size'),
      '#default_value' => 500,
      '#states' => [
        'visible' => [
          ':input[name="import_type"]' => ["checked" => TRUE],
        ],
      ],
    ];

    $form['purge_last'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge ONLY last imported data'),
      '#submit' => ['::purgeAction'],
    ];

    $form['purge_all_label'] = [
      '#markup' => $this->t("<br/>The following button is to be used carefully
                    because it will delete all previously imported Entities!<br/>"),
    ];
    $form['purge_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge ALL DATA'),
      '#submit' => ['::purgeAction'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param $data
   * @param $header
   *
   * @return void
   */
  public function rowHandler($data, $header) {

    [$node, $url, $title, $lang] = $data;
    $nid = NULL;
    if (!empty($node)) {
      $nid = $node;
      $seo_entity = $this->manager->getStorage('vactory_extended_seo')->loadByProperties([
        'node_id' => $node,
      ]);
      $seo_entity = reset($seo_entity);
    } elseif (!empty($title)) {
      $nid = $this->manager->getStorage('node')
        ->loadByProperties([
          'title' => trim($title),
          'langcode' => $lang
        ]);
      $nid = reset($nid);
      $seo_entity = $this->manager->loadByProperties(['node_id' => $nid]);
      $seo_entity = reset($seo_entity);
    } elseif (!empty($url)) {
      $path_alias_manager =  $this->manager->getStorage('path_alias');
      $alias_objects = $path_alias_manager->loadByProperties([
        'alias'     => $url,
        'langcode' => $lang
      ]);
      $alias = reset($alias_objects);
      $alias = $alias instanceof PathAlias ? $alias->getPath() : '';
      $nid = explode("/", $alias);
      $nid = end($nid);
      $seo_entity = $nid ? $this->manager->getStorage('vactory_extended_seo')
        ->loadByProperties(['node_id' => $nid]) : NULL;
      $seo_entity = reset($seo_entity);
    }

    if (empty($seo_entity)) {
      $seo_entity = [
        'name' => "node.$node",
        'node_id' => $nid,
        'user_id' => \Drupal::currentUser()->id(),
      ];
      foreach ($this->activeLanguages as $lang => $val) {
        if (!array_key_exists("hreflang_$lang", $header)) {
          continue;
        }
        $sanitizeId = str_replace('-', '_', $lang);
        $seo_entity["alternate_$sanitizeId"] = $data[$header["hreflang_$lang"]];
      }
      $seo_entity = $this->manager->getStorage('vactory_extended_seo')->create($seo_entity);
      $seo_entity->save();
    } else {
      foreach ($this->activeLanguages as $lang => $val) {
        if (!array_key_exists("hreflang_$lang", $header)) {
          continue;
        }
        $sanitizeId = str_replace('-', '_', $lang);
        $seo_entity->set("alternate_$sanitizeId", $data[$header["hreflang_$lang"]]);
      }
      $seo_entity->save();
    }
    $this->imported_ids[] = $seo_entity?->id();

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $created_ids = [];
    $import_type = $form_state->getValue('import_type');
    if ($import_type) {
      $this->batch_form_submit($form, $form_state);
      return;
    }
    // Get the uploaded file ID.
    $fid = $form_state->getValue('file_data')[0];
    $file = File::load($fid);
    if ($file) {
      $file_path = $file->getFileUri();
      $file_handle = fopen($file_path, 'rb');
      if ($file_handle !== FALSE) {
        // Skip the header row.
        $header = fgetcsv($file_handle, 0, ';');
//        Flip the array to use the header as keys.
        $header = array_flip($header);

        // Loop through each row in the CSV.
        while (($data = fgetcsv($file_handle, 0, ';')) !== FALSE) {
          $this->rowHandler($data, $header);
        }
        fclose($file_handle);
        $this->config('vactory_extended_seo_import.settings')
          ->set('file_data', $form_state->getValue('file_data'))
          ->set('last_imported_ids', $this->imported_ids)
          ->save();
        \Drupal::messenger()->addMessage($this->t('Fichier chargé avec succes'), MessengerInterface::TYPE_STATUS);
      }
      else {
        \Drupal::messenger()->addMessage($this->t('Impossible de lire le fichier'), MessengerInterface::TYPE_WARNING);
      }
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Aucun fichier CSV trouvé'), MessengerInterface::TYPE_WARNING);
    }
  }

  /**
   * @param int $mode
   *
   * @return void
   */
  public function purge(int $mode = self::DELETE_LAST) {
    try {
      $storage_handler = $this->manager->getStorage("vactory_extended_seo");
      $ids = [];
      if ($mode === self::DELETE_LAST) {
        $ids = $this->config('vactory_extended_seo_import.settings')->get('last_imported_ids');
        $ids = $storage_handler->loadMultiple($ids);

        $storage_handler?->delete($ids);

      } elseif ($mode === self::DELETE_ALL) {
        $ids = $storage_handler->loadMultiple();
        $storage_handler->delete($ids);
      }
      $this->config('vactory_extended_seo_import.settings')
        ->set('file_data', NULL)
        ->set('last_imported_ids', [])
        ->save();
    } catch (\Exception $e) {
      \Drupal::messenger()->addMessage($this->t('An error occurred plz check the logs'),
        MessengerInterface::TYPE_ERROR);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function purgeAction(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $element = $element['#id'];
    if (str_contains($element, 'last')) {
      $this->purge(self::DELETE_LAST);
      \Drupal::messenger()->addMessage(
        $this->t('Data from the last imported file were deleted !'),
        MessengerInterface::TYPE_STATUS);
    } else {
      $this->purge(self::DELETE_ALL);
      \Drupal::messenger()->addMessage(
        $this->t('All Extended SEO entities were deleted !'),
        MessengerInterface::TYPE_STATUS);

    }
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function batch_form_submit($form, FormStateInterface $form_state) {
    // Get the uploaded file ID.
    $fid = $form_state->getValue('file_data')[0];
    $file = File::load($fid);
    $file_path = $file?->getFileUri();
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Processing CSV file'))
      ->setFinishCallback([$this, 'batch_finished']);

    // Split the CSV processing into smaller chunks to avoid memory issues
    $batch_size = $form_state->getValue('batch_size') ?? 500;
    $batch = [];
    $rows = 0;
    if (($handle = fopen($file_path, "rb")) !== FALSE) {
      $header = fgetcsv($handle, 0, ';');
      //        Flip the array to use the header as keys.
      $header = array_flip($header);
      while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if ($rows % $batch_size === 0) {
          if (!empty($batch)) {
            $batch_builder->addOperation([$this, 'batch_process_data'], [$batch]);
          }
          $batch = [];
        }
        $batch[] = [
          'data' => $data,
          'header' => $header,
          'file_path' => $file_path,
          'file' => $form_state->getValue('file_data')
        ];
        $rows++;
      }
      fclose($handle);
    }

    if (!empty($batch)) {
      $batch_builder->addOperation([$this, 'batch_process_data'], [$batch]);
    }

    batch_set($batch_builder->toArray());
  }

  /**
   * @param $data
   * @param $context
   *
   * @return void
   */
  public function batch_process_data($data, &$context) {
    $file_path = '';
    $file = NULL;
    // Perform your processing on the $rows array
    foreach ($data as $row) {
      $this->rowHandler($row['data'], $row['header']);
      $file_path = $row['file_path'];
      $file = $row['file'];
    }
    // Update the progress
    $context['results']['processed_rows'] += count($data);
    $context['results']['file_data'] = $file;
    if ($context['results']['processed_ids']) {
      $context['results']['processed_ids'] += $this->imported_ids;
    } else {
      $context['results']['processed_ids'] = $this->imported_ids;
    }
    $context['message'] = t('Processed @count rows from file: @file', [
      '@count' => count($data),
      '@file' => $file_path,
    ]);
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   *
   * @return void
   */
  public function batch_finished($success, $results, $operations) {
    if ($success) {
      $this->config('vactory_extended_seo_import.settings')
        ->set('file_data', $results['file_data'])
        ->set('last_imported_ids', $results['processed_ids'])
        ->save();
      \Drupal::messenger()->addMessage(
        $this->t('Batch processing completed. Processed @count rows.', ['@count' => $results['processed_rows']]),
        MessengerInterface::TYPE_STATUS);
    } else {
      \Drupal::messenger()->addMessage($this->t('Batch processing encountered an error.'),
        MessengerInterface::TYPE_ERROR);
    }
  }
}
