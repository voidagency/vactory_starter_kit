<?php

namespace Drupal\vactory_content_diff\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_content_diff\Service\ContentDiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Compare content'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'content-diff-results-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Comparing content, please wait...'),
        ],
      ],
    ];

    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-diff-results-wrapper'],
    ];

    $rows = $form_state->get('content_diff_rows') ?: [];
    if (!empty($rows)) {
      $form['results_wrapper']['results_table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Title'), $this->t('Type'), $this->t('Status')],
        '#rows' => $rows,
        '#attributes' => ['class' => ['content-diff-results']],
      ];
    }

    // Attach module CSS.
    $form['#attached']['library'][] = 'vactory_content_diff/content_diff';

    return $form;
  }

  /**
   * AJAX callback to refresh the results table area.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
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

    // Persist URL for next time.
    $this->state->set('vactory_content_diff.remote_url', $remote_url);

    // Fetch and paginate remote nodes.
    $remote_nodes = $this->diffService->handlePagination($remote_url);

    if (empty($remote_nodes)) {
      $this->messenger()->addWarning($this->t('No remote nodes received or unable to fetch data.'));
      $form_state->set('content_diff_rows', []);
      $form_state->setRebuild(TRUE);
      return;
    }

    // Compare with local by UUID.
    $compared = $this->diffService->compareWithLocal($remote_nodes);

    // Transform to table rows for inline rendering in form.
    $table = $this->diffService->buildResultsTable($compared);

    // Convert rows to simple rows embedding CSS classes for the status.
    $rows = [];
    foreach ($compared as $row) {
      $rows[] = [
        'data' => [
          $row['title'] ?? '',
          $row['bundle'] ?? '',
          [
            'data' => [
              '#markup' => $row['status'] ?? '',
            ],
            'class' => [!empty($row['status_class']) ? $row['status_class'] : ''],
          ],
        ],
      ];
    }

    $form_state->set('content_diff_rows', $rows);
    $this->messenger()->addStatus($this->t('Comparison complete. Found @count items.', ['@count' => count($rows)]));
    $form_state->setRebuild(TRUE);
  }

}
