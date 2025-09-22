<?php

namespace Drupal\vactory_content_diff\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\vactory_content_diff\Service\ContentDiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\vactory_content_diff\ContentDiffStatus;
use Drupal\Core\Url;

/**
 * Provides the Content Diff admin form.
 */
class ContentDiffForm extends FormBase {

  /**
   * The diff service.
   *
   * @var \Drupal\vactory_content_diff\Service\ContentDiffService
   */
  protected $diffService;

  /**
   * Results storage.
   *
   * @var array
   */
  protected $results = [];

  /**
   * State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the form object.
   */
  public function __construct(ContentDiffService $diff_service, StateInterface $state) {
    $this->diffService = $diff_service;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_content_diff.service'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_diff_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'vactory-content-diff-form';

    $saved_remote_url = (string) $this->state->get('vactory_content_diff.remote_url', '');

    $form['remote_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Remote instance base URL'),
      '#description' => $this->t('Example: https://remote.example.com'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('remote_url') ?: $saved_remote_url,
    ];

    $saved_local_url = (string) $this->state->get('vactory_content_diff.local_url', '');

    $form['local_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Local instance base URL'),
      '#description' => $this->t('Example: https://remote.example.com'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('local_url') ?: $saved_local_url,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Compare content'),
      '#button_type' => 'primary',
    ];

    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-diff-results-wrapper'],
    ];

    // If we have compared dataset, display filters and filtered results.
    $compared = $form_state->get('content_diff_compared') ?: [];
    if (!empty($compared)) {
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
      $type_options = ['' => $this->t('- Any -')];
      foreach ($bundle_info as $machine_name => $info) {
        $type_options[$machine_name] = $info['label'] ?? $machine_name;
      }

      $status_options = ['' => $this->t('- Any -')];
      $status_options[ContentDiffStatus::SYNCHRONIZED['key']] = ContentDiffStatus::SYNCHRONIZED['label'];
      $status_options[ContentDiffStatus::MODIFIED['key']] = ContentDiffStatus::MODIFIED['label'];
      $status_options[ContentDiffStatus::NEW_ENTITY['key']] = ContentDiffStatus::NEW_ENTITY['label'];

      $form['results_wrapper']['filters'] = [
        '#type' => 'details',
        '#title' => $this->t('Filters'),
        '#open' => TRUE,
        '#attributes' => ['class' => ['content-diff-filters']],
      ];

      $form['results_wrapper']['filters']['filter_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => (string) $form_state->getValue('filter_title') ?: '',
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'event' => 'change',
          'wrapper' => 'content-diff-results-wrapper',
        ],
      ];
      $form['results_wrapper']['filters']['filter_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => $type_options,
        '#default_value' => (string) $form_state->getValue('filter_type') ?: '',
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'event' => 'change',
          'wrapper' => 'content-diff-results-wrapper',
        ],
      ];
      $form['results_wrapper']['filters']['filter_status'] = [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#options' => $status_options,
        '#default_value' => (string) $form_state->getValue('filter_status') ?: '',
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'event' => 'change',
          'wrapper' => 'content-diff-results-wrapper',
        ],
      ];

      // Build filtered rows from compared + current filter values.
      $filters = [
        'title' => (string) $form_state->getValue('filter_title') ?: '',
        'type' => (string) $form_state->getValue('filter_type') ?: '',
        'status' => (string) $form_state->getValue('filter_status') ?: '',
      ];
      $rows = $this->buildFilteredRows($compared, $filters);

      if (!empty($rows)) {
        $form['results_wrapper']['results_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Title'),
            $this->t('Type'),
            $this->t('Status'),
            $this->t('Diff'),
          ],
          '#rows' => $rows,
          '#attributes' => ['class' => ['content-diff-results']],
        ];
      }
    }

    // Attach module CSS and dialog.
    $form['#attached']['library'][] = 'vactory_content_diff/content_diff';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * AJAX callback to refresh the results and filters area.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['results_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $remote_url = trim((string) $form_state->getValue('remote_url'));
    if (!filter_var($remote_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('remote_url', $this->t('Please provide a valid URL.'));
      return;
    }
    // Basic scheme check.
    $scheme = parse_url($remote_url, PHP_URL_SCHEME);
    if (!in_array($scheme, ['http', 'https'], TRUE)) {
      $form_state->setErrorByName('remote_url', $this->t('URL must start with http or https.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $remote_url = trim((string) $form_state->getValue('remote_url'));
    $local_url = trim((string) $form_state->getValue('local_url'));

    // Persist URL for next time.
    $this->state->set('vactory_content_diff.remote_url', $remote_url);
    $this->state->set('vactory_content_diff.local_url', $local_url);

    // Fetch and paginate remote nodes.
    $remote_nodes = $this->diffService->handlePagination($remote_url);

    if (empty($remote_nodes)) {
      $this->messenger()->addWarning($this->t('No remote nodes received or unable to fetch data.'));
      $form_state->set('content_diff_rows', []);
      $form_state->set('content_diff_compared', []);
      $form_state->setRebuild(TRUE);
      return;
    }

    // Compare with local by UUID.
    $compared = $this->diffService->compareWithLocal($remote_nodes);

    // Persist full compared dataset for AJAX filtering.
    $form_state->set('content_diff_compared', $compared);

    // Build rows based on current filter values (if any).
    $filters = [
      'title' => (string) $form_state->getValue('filter_title') ?: '',
      'type' => (string) $form_state->getValue('filter_type') ?: '',
      'status' => (string) $form_state->getValue('filter_status') ?: '',
    ];
    $rows = $this->buildFilteredRows($compared, $filters);

    $form_state->set('content_diff_rows', $rows);
    $this->messenger()->addStatus($this->t('Comparison complete. Found @count items.', ['@count' => count($rows)]));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Build filtered table rows from compared dataset and filters.
   *
   * @param array $compared
   *   Dataset with keys: title, bundle, status_key, status, status_class.
   * @param array $filters
   *   Keys: title, type, status (status_key).
   *
   * @return array
   *   Table rows.
   */
  protected function buildFilteredRows(array $compared, array $filters): array {
    $rows = [];
    $title_filter = mb_strtolower($filters['title'] ?? '');
    $type_filter = $filters['type'] ?? '';
    $status_filter = $filters['status'] ?? '';

    foreach ($compared as $row) {
      $title = (string) ($row['title'] ?? '');
      $bundle = (string) ($row['bundle'] ?? '');
      $status = (string) ($row['status'] ?? '');
      $status_class = (string) ($row['status_class'] ?? '');
      $status_key = (string) ($row['status_key'] ?? '');
      $uuid = (string) ($row['uuid'] ?? ($row['id'] ?? ''));

      if ($title_filter !== '' && mb_strpos(mb_strtolower($title), $title_filter) === FALSE) {
        continue;
      }
      if ($type_filter !== '' && $bundle !== $type_filter) {
        continue;
      }
      if ($status_filter !== '' && $status_key !== $status_filter) {
        continue;
      }

      $diff_cell = ['data' => ['#markup' => '']];
      if ($status_key === ContentDiffStatus::MODIFIED['key'] && $uuid && $bundle) {
        $url = Url::fromRoute('vactory_content_diff.compare', [
          'bundle' => $bundle,
          'uuid' => $uuid,
        ],
        [
          'attributes' =>
            [
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
          $bundle,
          [
            'data' => [
              '#markup' => $status,
            ],
            'class' => [$status_class ?: ''],
          ],
          $diff_cell,
        ],
      ];
    }

    return $rows;
  }

}
