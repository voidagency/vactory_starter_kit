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
   * Constructs the form object.
   */
  public function __construct(FileSystemInterface $file_system, StateInterface $state, EntityTypeBundleInfoInterface $entity_type_bundle_info, PrivateTempStoreFactory $temp_store_factory) {
    $this->fileSystem = $file_system;
    $this->state = $state;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('state'),
      $container->get('entity_type.bundle.info'),
      $container->get('tempstore.private')
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
    $form['#attributes']['class'][] = 'vactory-content-diff-csv-form';

    // Add a wrapper for the entire form content.
    $form['#prefix'] = '<div id="vactory-content-diff-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['instruction'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'vactory-diff-content-instruction'],
    ];

    $instructions = '<div class="instruction-intro">';
    $instructions .= '<h3>' . $this->t('Instructions') . '</h3>';
    $instructions .= '<p>' . $this->t("This interface lists the content differences after running the command <strong>drush vactory_diff_content_fetch</strong>") . '</p>';
    $instructions .= '<p>' . $this->t("The command uses the remote URL configured in Vactory Diff Settings.") . '</p>';
    $instructions .= '<p>' . $this->t("If you have changed the remote URL, make sure to rerun the command to fetch the updated differences.") . '</p>';
    $instructions .= '</div>';

    $form['instruction']['intro'] = [
      '#type' => 'markup',
      '#markup' => $instructions,
    ];

    // Add status legend in instructions.
    $status_legend = '<div class="status-legend">';
    $status_legend .= '<h3>' . $this->t('Status Legend') . '</h3>';
    $status_legend .= '<div class="status-items">';
    foreach (ContentDiffConst::STATUS as $key => $status) {
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

    // Check if CSV file exists.
    $csv_path = ContentDiffConst::FILE_PATH . '/' . ContentDiffConst::FILE_NAME;
    $real_path = $this->fileSystem->realpath($csv_path);

    if (!$real_path || !file_exists($real_path)) {
      $form['no_file'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('No CSV report found. Please run the drush command first: drush vactory_diff_content_fetch') . '</div>',
      ];
      return $form;
    }

    // Read CSV file.
    $csv_data = $this->readCsvFile($real_path);

    if (empty($csv_data)) {
      $form['no_data'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('CSV file is empty or could not be read. Please run the drush command first: drush vactory_diff_content_fetch') . '</div>',
      ];
      return $form;
    }

    // Get saved filters from temp store.
    $store = $this->tempStoreFactory->get('vactory_diff_content');
    $saved_filters = $store->get('filters') ?: [];

    // Build filters.
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

    // Check if the type has changed and reset bundle if needed.
    $previous_type = $form_state->get('previous_type') ?: '';
    if ($selected_type !== $previous_type) {
      // Type has changed, reset the bundle value.
      $form_state->setValue('filter_bundle', '');
      $form_state->set('previous_type', $selected_type);
    }

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

    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-diff-results-wrapper'],
    ];

    // Build filtered rows - only apply filters if form was submitted (not AJAX)
    $filters = [
      'title' => '',
      'type' => '',
      'bundle' => '',
      'status' => [],
    ];

    // Get temp store for this user.
    $store = $this->tempStoreFactory->get('vactory_diff_content');
    $saved_filters = $store->get('filters') ?: [];

    // Check if the form was actually submitted (not just AJAX callback)
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && isset($triggering_element['#name']) && $triggering_element['#name'] === 'filter') {
      // Form was submitted via Filter button, apply filters from form values.
      $filters = [
        'title' => (string) $form_state->getValue('filter_title') ?: '',
        'type' => (string) $form_state->getValue('filter_type') ?: '',
        'bundle' => (string) $form_state->getValue('filter_bundle') ?: '',
        'status' => $form_state->getValue('filter_status') ?: [],
      ];
    }
    elseif (!empty($saved_filters)) {
      // Use saved filters from temp store.
      $filters = $saved_filters;
    }

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

    // Attach module CSS and dialog.
    $form['#attached']['library'][] = 'vactory_diff_content/content_diff';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
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

    foreach (ContentDiffConst::SUPPORTED_ENTITY_TYPES as $entity_type) {

      $options[$entity_type] = $entity_type;
    }
    return $options;
  }

  /**
   * Get status options.
   */
  protected function getStatusOptions(): array {
    $options = ['' => $this->t('- Any -')];
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

}
