<?php

namespace Drupal\vactory_diff_content\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\vactory_diff_content\ContentDiffConst;
use Drupal\vactory_diff_content\Service\ContentDiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Provides the Content Diff CSV display form.
 */
class ContentDiffCsvForm extends FormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The content diff service.
   *
   * @var \Drupal\vactory_diff_content\Service\ContentDiffService
   */
  protected $contentDiffService;

  /**
   * Constructs the form object.
   */
  public function __construct(FileSystemInterface $file_system, StateInterface $state, EntityTypeBundleInfoInterface $entity_type_bundle_info, PrivateTempStoreFactory $temp_store_factory, ContentDiffService $content_diff_service) {
    $this->fileSystem = $file_system;
    $this->state = $state;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->tempStoreFactory = $temp_store_factory;
    $this->contentDiffService = $content_diff_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('state'),
      $container->get('entity_type.bundle.info'),
      $container->get('tempstore.private'),
      $container->get('vactory_diff_content.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_diff_content_csv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->buildFormBase($form);
    $this->buildInstructions($form);
    $this->buildGenerateButton($form);

    $csv_data = $this->loadCsvData($form);
    if ($csv_data === NULL) {
      return $form;
    }

    $saved_filters = $this->getSavedFilters();
    $this->buildFilters($form, $form_state, $csv_data, $saved_filters);
    $this->buildResultsTable($form, $csv_data, $form_state, $saved_filters);

    return $form;
  }

  /**
   * Build base form structure.
   *
   * @param array &$form
   *   Form array (passed by reference).
   */
  protected function buildFormBase(array &$form): void {
    $form['#attributes']['class'][] = 'vactory-content-diff-csv-form';
    $form['#attached']['library'][] = 'vactory_diff_content/content_diff';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#prefix'] = '<div id="vactory-content-diff-form-wrapper">';
    $form['#suffix'] = '</div>';
  }

  /**
   * Build instructions section.
   *
   * @param array &$form
   *   Form array (passed by reference).
   */
  protected function buildInstructions(array &$form): void {
    $form['instruction'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'vactory-diff-content-instruction'],
    ];

    $instructions = '<div class="instruction-intro">';
    $instructions .= '<h3>' . $this->t('Instructions') . '</h3>';
    $instructions .= '<p>' . $this->t("Generate the content diff report using one of the following methods:") . '</p>';
    $instructions .= '<ul>';
    $instructions .= '<li><strong>UI:</strong> Click "Generate Diff Report" button below</li>';
    $instructions .= '<li><strong>Command:</strong> Run <code>drush vactory_diff_content_fetch</code> (or <code>drush vcd-fetch</code>)</li>';
    $instructions .= '</ul>';
    $instructions .= '<p>' . $this->t("The report uses the remote URL configured in Vactory Diff Settings.") . '</p>';
    $instructions .= '</div>';

    $form['instruction']['intro'] = [
      '#type' => 'markup',
      '#markup' => $instructions,
    ];

    $status_legend = '<div class="status-legend">';
    $status_legend .= '<h3>' . $this->t('Status Legend') . '</h3>';
    $status_legend .= '<div class="status-items">';
    foreach (ContentDiffConst::STATUS as $status) {
      $status_legend .= '<div class="status-item ' . $status['class'] . '">';
      $status_legend .= '<span class="status-label">' . $status['label'] . '</span>';
      $status_legend .= '<span class="status-description">' . $status['description'] . '</span>';
      $status_legend .= '</div>';
    }
    $status_legend .= '</div>';
    $status_legend .= '</div>';

    $form['instruction']['status_legend'] = [
      '#type' => 'markup',
      '#markup' => $status_legend,
    ];
  }

  /**
   * Build generate button.
   *
   * @param array &$form
   *   Form array (passed by reference).
   */
  protected function buildGenerateButton(array &$form): void {
    $form['generate_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Diff Report'),
      '#submit' => ['::generateReport'],
    ];
  }

  /**
   * Load CSV data and handle errors.
   *
   * @param array &$form
   *   Form array (passed by reference).
   *
   * @return array|null
   *   CSV data array or NULL if file doesn't exist or is empty.
   */
  protected function loadCsvData(array &$form): ?array {
    $csv_path = ContentDiffConst::FILE_PATH . '/' . ContentDiffConst::FILE_NAME;
    $real_path = $this->fileSystem->realpath($csv_path);

    if (!$real_path || !file_exists($real_path)) {
      $form['no_file'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('No CSV report found. Please click "Generate Diff Report" button OR run the drush command first: drush vactory_diff_content_fetch') . '</div>',
      ];
      return NULL;
    }

    $csv_data = $this->readCsvFile($real_path);
    if (empty($csv_data)) {
      $form['no_data'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('CSV file is empty or could not be read. Please run the drush command first: drush vactory_diff_content_fetch') . '</div>',
      ];
      return NULL;
    }

    return $csv_data;
  }

  /**
   * Get saved filters from temp store.
   *
   * @return array
   *   Saved filters array.
   */
  protected function getSavedFilters(): array {
    $store = $this->tempStoreFactory->get('vactory_diff_content');
    return $store->get('filters') ?: [];
  }

  /**
   * Build filters section.
   *
   * @param array &$form
   *   Form array (passed by reference).
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $csv_data
   *   CSV data.
   * @param array $saved_filters
   *   Saved filters.
   */
  protected function buildFilters(array &$form, FormStateInterface $form_state, array $csv_data, array $saved_filters): void {
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['content-diff-filters']],
    ];

    $form['filters']['filter_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $form_state->getValue('filter_title') ?: ($saved_filters['title'] ?? ''),
    ];

    $form['filters']['filter_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $this->getTypeOptions($csv_data),
      '#default_value' => $form_state->getValue('filter_type') ?: ($saved_filters['type'] ?? ''),
      '#ajax' => [
        'callback' => '::ajaxRefreshForm',
        'event' => 'change',
        'wrapper' => 'vactory-content-diff-form-wrapper',
      ],
    ];

    $selected_type = $form_state->getValue('filter_type') ?: ($saved_filters['type'] ?? '');
    $this->handleTypeChange($form_state, $selected_type);

    if ($selected_type) {
      $form['filters']['filter_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $this->getBundleOptions($selected_type),
        '#default_value' => $form_state->getValue('filter_bundle') ?: ($saved_filters['bundle'] ?? ''),
      ];
    }

    $form['filters']['filter_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => $this->getStatusOptions(),
      '#multiple' => TRUE,
      '#default_value' => $form_state->getValue('filter_status') ?: ($saved_filters['status'] ?? []),
    ];

    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#name' => 'filter',
    ];

    $form['filters']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#name' => 'reset',
      '#submit' => ['::resetFilters'],
    ];
  }

  /**
   * Handle type change and reset bundle if needed.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $selected_type
   *   Selected type.
   */
  protected function handleTypeChange(FormStateInterface $form_state, string $selected_type): void {
    $previous_type = $form_state->get('previous_type') ?: '';
    if ($selected_type !== $previous_type) {
      $form_state->setValue('filter_bundle', '');
      $form_state->set('previous_type', $selected_type);
    }
  }

  /**
   * Build results table.
   *
   * @param array &$form
   *   Form array (passed by reference).
   * @param array $csv_data
   *   CSV data.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $saved_filters
   *   Saved filters.
   */
  protected function buildResultsTable(array &$form, array $csv_data, FormStateInterface $form_state, array $saved_filters): void {
    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-diff-results-wrapper'],
    ];

    $filters = $this->getActiveFilters($form_state, $saved_filters);
    $rows = $this->buildFilteredRows($csv_data, $filters);

    if (!empty($rows)) {
      $form['results_wrapper']['results_table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Title'),
          $this->t('UUID'),
          $this->t('Type'),
          $this->t('Bundle'),
          $this->t('Status'),
          $this->t('Diff'),
        ],
        '#rows' => $rows,
        '#attributes' => ['class' => ['content-diff-results']],
      ];
    }
  }

  /**
   * Get active filters from form state or saved filters.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $saved_filters
   *   Saved filters.
   *
   * @return array
   *   Active filters array.
   */
  protected function getActiveFilters(FormStateInterface $form_state, array $saved_filters): array {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && isset($triggering_element['#name']) && $triggering_element['#name'] === 'filter') {
      return [
        'title' => (string) $form_state->getValue('filter_title') ?: '',
        'type' => (string) $form_state->getValue('filter_type') ?: '',
        'bundle' => (string) $form_state->getValue('filter_bundle') ?: '',
        'status' => $form_state->getValue('filter_status') ?: [],
      ];
    }

    if (!empty($saved_filters)) {
      return $saved_filters;
    }

    return [
      'title' => '',
      'type' => '',
      'bundle' => '',
      'status' => [],
    ];
  }

  /**
   * AJAX callback to refresh the form when type changes.
   */
  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Reset filters handler.
   */
  public function resetFilters(array &$form, FormStateInterface $form_state) {
    // Clear filters from temp store.
    $store = $this->tempStoreFactory->get('vactory_diff_content');
    $store->delete('filters');

    // Redirect to the same page.
    $form_state->setRedirect('<current>');
  }

  /**
   * Read CSV file and return data array.
   */
  protected function readCsvFile(string $file_path): array {
    $data = [];

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
      // Skip header row.
      $header = fgetcsv($handle);

      while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 5) {
          $data[] = [
            'title' => $row[0] ?? '',
            'uuid' => $row[1] ?? '',
            'type' => $row[2] ?? '',
            'bundle' => $row[3] ?? '',
            'status' => $row[4] ?? '',
          ];
        }
      }
      fclose($handle);
    }

    return $data;
  }

  /**
   * Get type options from CSV data.
   */
  protected function getTypeOptions(array $csv_data): array {
    $options = ['' => $this->t('- Any -')];

    // Get configured entity types from settings.
    $config = \Drupal::config('vactory_diff_config_client.settings');
    $configured_types = $config->get('content_entity_types');

    // Use configured types if available.
    if (!empty($configured_types)) {
      foreach ($configured_types as $entity_type) {
        $options[$entity_type] = $entity_type;
      }
    }

    return $options;
  }

  /**
   * Get status options.
   */
  protected function getStatusOptions(): array {
    $options = [];
    foreach (ContentDiffConst::STATUS as $key => $status) {
      $options[$key] = $status['label'];
    }
    return $options;
  }

  /**
   * Get bundle options for a given entity type.
   */
  protected function getBundleOptions(string $entity_type): array {
    $options = ['' => $this->t('- Any -')];

    if (empty($entity_type)) {
      return $options;
    }

    try {
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

      foreach ($bundle_info as $bundle_id => $bundle_data) {
        $options[$bundle_id] = $bundle_data['label'] ?? $bundle_id;
      }
    }
    catch (\Exception $e) {
      // If there's an error getting bundles, just return the basic options.
      \Drupal::logger('vactory_diff_content')->warning('Error getting bundles for entity type @type: @message', [
        '@type' => $entity_type,
        '@message' => $e->getMessage(),
      ]);
    }

    return $options;
  }

  /**
   * Get status descriptions for field description.
   */
  protected function getStatusDescriptions(): string {
    $descriptions = [];
    foreach (ContentDiffConst::STATUS as $status) {
      $descriptions[] = $status['label'] . ': ' . $status['description'];
    }
    return implode('<br>', $descriptions);
  }

  /**
   * Build filtered table rows from CSV data and filters.
   */
  protected function buildFilteredRows(array $csv_data, array $filters): array {
    $rows = [];
    $title_filter = mb_strtolower($filters['title'] ?? '');
    $type_filter = $filters['type'] ?? '';
    $bundle_filter = $filters['bundle'] ?? '';
    $status_filter = $filters['status'] ?? [];

    foreach ($csv_data as $row) {
      $title = (string) ($row['title'] ?? '');
      $uuid = (string) ($row['uuid'] ?? '');
      $type = (string) ($row['type'] ?? '');
      $bundle = (string) ($row['bundle'] ?? '');
      $status_key = (string) ($row['status'] ?? '');

      // Apply filters.
      if ($title_filter !== '' && mb_strpos(mb_strtolower($title), $title_filter) === FALSE) {
        continue;
      }
      if ($type_filter !== '' && $type !== $type_filter) {
        continue;
      }
      if ($bundle_filter !== '' && $bundle !== $bundle_filter) {
        continue;
      }
      if (!empty($status_filter) && !in_array($status_key, $status_filter)) {
        continue;
      }

      // Determine status object.
      $status = ContentDiffConst::STATUS[$status_key];

      // Build diff cell.
      $diff_cell = ['data' => ['#markup' => '']];
      if ($status_key === 'modified' && $uuid && $bundle) {
        $url = Url::fromRoute('vactory_diff_content.compare', [
          'type' => $type,
          'bundle' => $bundle,
          'uuid' => $uuid,
        ],
        [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => '100%']),
          ],
        ]);
        $link = Link::fromTextAndUrl($this->t('View diff'), $url)->toRenderable();
        $diff_cell = [
          'data' => $link,
        ];
      }

      $rows[] = [
        'data' => [
          $title,
          $uuid,
          $type,
          $bundle,
          [
            'data' => [
              '#markup' => $status['label'],
            ],
            'class' => [$status['class'] ?: ''],
          ],
          $diff_cell,
        ],
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save filters to temp store.
    $filters = [
      'title' => (string) $form_state->getValue('filter_title') ?: '',
      'type' => (string) $form_state->getValue('filter_type') ?: '',
      'bundle' => (string) $form_state->getValue('filter_bundle') ?: '',
      'status' => $form_state->getValue('filter_status') ?: [],
    ];

    $store = $this->tempStoreFactory->get('vactory_diff_content');
    $store->set('filters', $filters);

    // Redirect to the same page to avoid form resubmission warning.
    $form_state->setRedirect('<current>');
  }

  /**
   * Submit handler for Generate Diff Report button.
   */
  public function generateReport(array &$form, FormStateInterface $form_state) {
    // Use the injected service to call the centralized method.
    $result = $this->contentDiffService->generateDiffReport();

    if ($result['success']) {
      // Show success message.
      $this->messenger()->addStatus($this->t('Diff report generated successfully! Total entities: @count, Results: @results', [
        '@count' => $result['total_entities'],
        '@results' => $result['results_count'],
      ]));
    }
    else {
      // Show error message.
      $this->messenger()->addError($this->t('Error generating diff report: @message', [
        '@message' => $result['message'],
      ]));
    }

    // Reload the page to show the results.
    $form_state->setRedirect('<current>');
  }

}
