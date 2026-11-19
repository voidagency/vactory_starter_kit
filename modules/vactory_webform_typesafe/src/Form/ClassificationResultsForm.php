<?php

namespace Drupal\vactory_webform_typesafe\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use Drupal\vactory_webform_typesafe\Batch\ProcessPendingBatch;
use Drupal\webform\WebformInterface;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;

/**
 * Lists and filters the effective classifications for one Webform.
 */
final class ClassificationResultsForm extends AnalysisFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_webform_typesafe_results';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformInterface $webform = NULL) {
    $form_state->set('webform_id', $webform->id());
    $form['#cache']['max-age'] = 0;
    $form['#attached']['library'][] = 'vactory_webform_typesafe/results';
    $request = $this->getRequest()->query;
    $settings = $this->manager->settings($webform);
    $questions = $this->configuredQuestions($settings);
    $form['filters'] = $this->buildFilters($request, $questions);
    $this->addPendingAction($form);
    $rows = [];
    foreach ($this->loadRecords($webform, $request, $form['filters'], $questions) as $sid => $record) {
      $submission = $record->get('submission')->entity;
      if ($submission && $submission->access('view')) {
        $rows[$sid] = $this->buildRow($record, $submission, $sid, $webform, $settings);
      }
    }
    $form['records'] = [
      '#type' => 'tableselect',
      '#attributes' => ['class' => ['vwt-results']],
      '#header' => [
        'id' => $this->t('Soumission et contenu'),
        'status' => $this->t('État'),
        'review' => $this->t('Vérification'),
        'summary' => $this->t('Classification'),
      ],
      '#options' => $rows,
      '#empty' => $this->t('Aucune analyse correspondante.'),
    ];
    $form['pager'] = ['#type' => 'pager'];
    $form['actions']['#type'] = 'actions';
    $form['actions']['analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Réanalyser les soumissions sélectionnées'),
      '#access' => $this->currentUser()->hasPermission('run vactory webform typesafe'),
    ];
    $form['note'] = [
      '#markup' => $this->t('Une nouvelle analyse remplace les résultats et corrections uniquement après sa réussite. Cliquez sur « Traiter les soumissions en attente » pour lancer les analyses immédiatement, ou laissez cron les traiter automatiquement.'),
    ];
    return $form;
  }

  /**
   * The questions configured on the webform, or none if invalid.
   */
  protected function configuredQuestions(?array $settings): array {
    if (!$settings) {
      return [];
    }
    try {
      return QuestionDefinition::parse($settings['questions']);
    }
    catch (\Throwable) {
      // Invalid settings: the question filter only offers "Any question".
      return [];
    }
  }

  /**
   * Builds the filters fieldset.
   */
  protected function buildFilters($request, array $questions): array {
    $filters = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#open' => TRUE,
    ];
    $filters['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Processing status'),
      '#options' => [
        '' => $this->t('All'),
        'pending' => $this->t('Pending'),
        'processing' => $this->t('Processing'),
        'completed' => $this->t('Completed'),
        'failed' => $this->t('Failed'),
        'stale' => $this->t('Outdated'),
      ],
      '#default_value' => $request->get('status', ''),
    ];
    $filters['review'] = [
      '#type' => 'select',
      '#title' => $this->t('Review status'),
      '#options' => [
        '' => $this->t('All'),
        'needs_review' => $this->t('Needs review'),
        'unreviewed' => $this->t('Unreviewed'),
        'reviewed' => $this->t('Reviewed'),
      ],
      '#default_value' => $request->get('review', ''),
    ];
    $options = ['' => $this->t('Any question')];
    foreach ($questions as $id => $q) {
      $options[$id] = $q['label'];
    }
    $filters['question'] = [
      '#type' => 'select',
      '#title' => $this->t('Classification question'),
      '#options' => $options,
      '#default_value' => $request->get('question', ''),
    ];
    $filters['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classification value'),
      '#description' => $this->t('Exact category key, numeric score, or yes/no. Filters use staff corrections where present.'),
      '#default_value' => $request->get('value', ''),
    ];
    $filters['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply filters'),
      '#submit' => ['::filterResults'],
      '#limit_validation_errors' => [
        ['status'],
        ['review'],
        ['question'],
        ['value'],
      ],
    ];
    return $filters;
  }

  /**
   * Adds the "process pending submissions" action for allowed users.
   */
  protected function addPendingAction(array &$form): void {
    if (!$this->currentUser()->hasPermission('run vactory webform typesafe')) {
      return;
    }
    $form['process_pending'] = [
      '#type' => 'submit',
      '#value' => $this->t('Traiter les soumissions en attente'),
      '#submit' => ['::processPending'],
      '#limit_validation_errors' => [],
    ];
    $form['pending_help'] = [
      '#markup' => '<p>' . $this->t('Traitez immédiatement toutes les soumissions en attente de ce formulaire, quels que soient les filtres ou les lignes sélectionnées. Gardez la page de progression ouverte jusqu’à la fin du traitement.') . '</p>',
    ];
  }

  /**
   * Loads the analyses of the current page, with the request filters.
   */
  protected function loadRecords(WebformInterface $webform, $request, array $filters, array $questions): array {
    $storage = $this->entities->getStorage('vactory_webform_analysis');
    // Check submission access for each row in addition to route access.
    $query = $storage->getQuery()->accessCheck(FALSE)->condition('webform', $webform->id())->sort('id', 'DESC');
    foreach (['status' => 'status', 'review' => 'review_status'] as $filter => $field) {
      if (in_array($request->get($filter), array_keys($filters[$filter]['#options']), TRUE) && $request->get($filter) !== '') {
        $query->condition($field, $request->get($filter));
      }
    }
    if (isset($questions[$request->get('question', '')]) && trim($request->get('value', '')) !== '') {
      $query->condition('labels', $request->get('question') . ':' . trim($request->get('value')));
    }
    return $storage->loadMultiple($query->pager(25)->execute());
  }

  /**
   * Builds the table row of an analysis.
   */
  protected function buildRow($record, $submission, $sid, WebformInterface $webform, ?array $settings): array {
    return [
      'id' => ['data' => $this->buildPreview($submission, $sid, $webform, $settings)],
      'status' => ['data' => $this->statusBadge($record->get('status')->value, FALSE)],
      'review' => ['data' => $this->statusBadge($record->get('review_status')->value, TRUE)],
      'summary' => ['data' => $this->buildSummary($record)],
    ];
  }

  /**
   * Builds the classification summary of an analysis.
   */
  protected function buildSummary($record): array {
    $summary = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vwt-classifications']],
    ];
    if ($record->get('status')->value !== 'completed') {
      $summary['empty'] = ['#plain_text' => $this->t('Classification indisponible pour le moment.')];
      return $summary;
    }
    $stored_questions = $record->decoded('questions');
    $overrides = $record->decoded('overrides');
    foreach ($record->decoded('results')['answers'] ?? [] as $id => $answer) {
      if (isset($stored_questions[$id])) {
        $value = $overrides[$id] ?? QuestionDefinition::value($stored_questions[$id], $answer);
        $summary[] = $this->buildClassification($id, $stored_questions[$id], $value, isset($overrides[$id]));
      }
    }
    return $summary;
  }

  /**
   * Builds the display of one classification.
   */
  protected function buildClassification(string $id, array $question, $value, bool $overridden): array {
    $display = QuestionDefinition::displayValue($question, $value);
    if ($question['type'] === 'score') {
      $display = str_replace('.', ',', $value) . ' / ' . (count($question['criteria']) - 1);
    }
    $score_detail = NULL;
    if ($id === 'urgency' && $this->isBuiltinUrgencyScale($question)) {
      $score_detail = $this->t('Score : @score', ['@score' => $display]);
      $display = $this->urgencyLabel((float) $value);
    }
    $item = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vwt-classification']],
      'label' => $this->htmlTag('span', $question['label'], 'vwt-label'),
      'value' => $this->htmlTag('strong', $display),
    ];
    if ($score_detail !== NULL) {
      $item['score'] = $this->htmlTag('small', $score_detail);
    }
    if ($overridden) {
      $item['correction'] = $this->htmlTag('small', $this->t('Corrigé manuellement'));
    }
    return $item;
  }

  /**
   * Whether a question uses the built-in urgency scale.
   *
   * Only the built-in urgency scale is labelled; custom scales keep their own
   * numeric representation rather than inheriting its meaning.
   */
  protected function isBuiltinUrgencyScale(array $question): bool {
    $urgency_scales = [
      QuestionDefinition::defaults()['urgency']['criteria'],
      [
        'Low: no deadline or immediate impact',
        'Normal: routine request',
        'High: explicit near deadline or significant current disruption',
      ],
    ];
    return $question['type'] === 'score' && in_array($question['criteria'], $urgency_scales, TRUE);
  }

  /**
   * Label of an urgency score.
   */
  protected function urgencyLabel(float $score) {
    return match (TRUE) {
      $score <= 0 => $this->t('Urgence faible'),
      $score < 1 => $this->t('Urgence faible à normale'),
      $score == 1 => $this->t('Urgence normale'),
      $score < 2 => $this->t('Urgence normale à élevée'),
      default => $this->t('Urgence élevée'),
    };
  }

  /**
   * Builds an html_tag element with plain text.
   */
  protected function htmlTag(string $tag, $text, ?string $class = NULL): array {
    $element = ['#type' => 'html_tag', '#tag' => $tag];
    if ($class !== NULL) {
      $element['#attributes'] = ['class' => [$class]];
    }
    $element['text'] = ['#plain_text' => $text];
    return $element;
  }

  /**
   * Builds the submission link and an excerpt of its analyzed fields.
   */
  protected function buildPreview($submission, $sid, WebformInterface $webform, ?array $settings): array {
    $preview = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vwt-submission']],
      'link' => Link::fromTextAndUrl($this->t('Soumission #@id', ['@id' => $sid]), Url::fromRoute('vactory_webform_typesafe.analysis', [
        'webform' => $webform->id(),
        'webform_submission' => $sid,
      ]))->toRenderable(),
    ];
    $input = \Drupal::service('vactory_webform_typesafe.input')->build($submission, $settings['fields'] ?? [], PHP_INT_MAX);
    foreach ($input as $field) {
      $text = is_array($field['value']) ? implode(', ', $field['value']) : (string) $field['value'];
      $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
      $preview[] = [
        '#type' => 'container',
        'label' => $this->htmlTag('span', $field['label'], 'vwt-label'),
        'excerpt' => $this->htmlTag('p', Unicode::truncate(trim($text), 180, TRUE, TRUE)),
      ];
    }
    if (!$input) {
      $preview['empty'] = ['#plain_text' => $this->t('Aucun contenu à afficher dans les champs sélectionnés.')];
    }
    return $preview;
  }

  /**
   * Redirects to the selected filters without changing any analysis.
   */
  public function filterResults(array &$form, FormStateInterface $form_state) {
    $filters = array_intersect_key($form_state->getValues(), array_flip([
      'status',
      'review',
      'question',
      'value',
    ]));
    $form_state->setRedirect('vactory_webform_typesafe.results', [
      'webform' => $form_state->get('webform_id'),
    ], [
      'query' => array_filter($filters, static fn($v) => $v !== ''),
    ]);
  }

  /**
   * Builds a readable status badge with a fixed set of style variants.
   */
  private function statusBadge(string $status, bool $review): array {
    $labels = $review ? [
      'needs_review' => $this->t('À vérifier'),
      'unreviewed' => $this->t('Non vérifié'),
      'reviewed' => $this->t('Vérifié'),
    ] : [
      'pending' => $this->t('En attente'),
      'processing' => $this->t('En cours'),
      'completed' => $this->t('Terminée'),
      'failed' => $this->t('En échec'),
      'stale' => $this->t('À actualiser'),
    ];
    $tone = match ($status) {
      'completed', 'reviewed' => 'success',
      'needs_review', 'stale' => 'warning',
      'failed' => 'error',
      default => 'neutral',
    };
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['vwt-badge', 'vwt-badge--' . $tone]],
      'text' => ['#plain_text' => $labels[$status] ?? $status],
    ];
  }

  /**
   * Starts immediate processing of this Webform's pending analyses.
   */
  public function processPending(array &$form, FormStateInterface $form_state) {
    $webform_id = $form_state->get('webform_id');
    ProcessPendingBatch::checkAccess($webform_id);
    $storage = $this->entities->getStorage('vactory_webform_analysis');
    $ids = $storage->getQuery()->accessCheck(FALSE)
      ->condition('webform', $webform_id)
      ->condition('status', 'pending')
      ->sort('id')->execute();
    if ($ids) {
      batch_set([
        'title' => $this->t('Traitement des soumissions en attente'),
        'operations' => array_map(static fn($sid) => [
          [ProcessPendingBatch::class, 'process'], [$webform_id, (int) $sid],
        ], array_values($ids)),
        'finished' => [ProcessPendingBatch::class, 'finished'],
      ]);
    }
    else {
      $this->messenger()->addStatus($this->t('Aucune soumission en attente à traiter.'));
    }
    $form_state->setRedirect('vactory_webform_typesafe.results', ['webform' => $webform_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->currentUser()->hasPermission('run vactory webform typesafe')) {
      throw new AccessDeniedHttpException();
    }
    $count = 0;
    foreach (array_filter($form_state->getValue('records', [])) as $sid) {
      $submission = $this->entities->getStorage('webform_submission')->load($sid);
      if ($submission && $submission->getWebform()->id() === $form_state->get('webform_id') && $submission->access('view')) {
        try {
          $count += (int) $this->manager->enqueue($submission, TRUE);
        }
        catch (\Throwable) {
          $this->messenger()->addError($this->t('Could not queue submission @sid. Check its selected fields and input size.', [
            '@sid' => $sid,
          ]));
        }
      }
    }
    $this->messenger()->addStatus($this->t('Queued @count submissions.', [
      '@count' => $count,
    ]));
    $form_state->setRebuild();
  }

}
