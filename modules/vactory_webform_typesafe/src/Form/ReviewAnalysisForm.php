<?php

namespace Drupal\vactory_webform_typesafe\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;

/**
 * Displays model decisions and stores separate staff corrections.
 */
final class ReviewAnalysisForm extends AnalysisFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_webform_typesafe_review';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformInterface $webform = NULL, ?WebformSubmissionInterface $webform_submission = NULL) {
    $sid = (int) $webform_submission->id();
    $form_state->set('sid', $sid);
    $record = $this->manager->load($sid);
    $form['#cache']['max-age'] = 0;
    $can_review = $this->currentUser()->hasPermission('review vactory webform typesafe');
    if ($record) {
      $form['analysis_generation'] = [
        '#type' => 'hidden',
        '#default_value' => $record->get('generation')->value,
      ];
      $form['analysis_review_version'] = [
        '#type' => 'hidden',
        '#default_value' => hash('sha256', json_encode([
          $record->get('overrides')->value,
          $record->get('reviewer')->value,
          $record->get('reviewed')->value,
          $record->get('review_status')->value,
        ])),
      ];
      $form['status'] = [
        '#plain_text' => $this->t('Status: @status. Review: @review. Model: @model. Attempts: @attempts.', [
          '@status' => $record->get('status')->value,
          '@review' => $record->get('review_status')->value,
          '@model' => $record->get('model')->value,
          '@attempts' => $record->get('attempts')->value,
        ]),
      ];
      if ($record->get('error')->value) {
        $form['error'] = ['#plain_text' => $record->get('error')->value];
      }
      try {
        $snapshot = $this->manager->snapshot($webform_submission);
      }
      catch (\Throwable) {
        $snapshot = NULL;
      }
      $outdated = !$snapshot || $snapshot['fingerprint'] !== $record->get('fingerprint')->value;
      if ($outdated) {
        $form['outdated'] = [
          '#markup' => $this->t('This analysis is outdated or its handler is disabled. Reanalyze before reviewing.'),
        ];
      }
      $can_review = $can_review && !$outdated && $record->get('status')->value === 'completed';
      $questions = $record->decoded('questions');
      $overrides = $record->decoded('overrides');
      $form['corrections'] = ['#tree' => TRUE];
      // Retain prior results during retries without presenting them as current.
      if ($record->get('status')->value === 'completed') {
        foreach ($record->decoded('results')['answers'] ?? [] as $id => $answer) {
          if (!isset($questions[$id])) {
            continue;
          }
          $q = $questions[$id];
          $form['answers'][$id] = [
            '#type' => 'details',
            '#title' => $q['label'],
            '#open' => TRUE,
          ];
          $form['answers'][$id]['value'] = [
            '#plain_text' => $this->t('Model result: @value', [
              '@value' => QuestionDefinition::displayValue($q, QuestionDefinition::value($q, $answer)),
            ]),
          ];
          if (isset($answer['confidence'])) {
            $form['answers'][$id]['confidence'] = [
              '#plain_text' => $this->t('Confidence: @value%', [
                '@value' => round($answer['confidence'] * 100, 1),
              ]),
            ];
          }
          if ($q['type'] === 'noul') {
            $form['answers'][$id]['probability'] = [
              '#plain_text' => $this->t('Probability that the statement is true: @value%', [
                '@value' => round($answer['noul'] * 100, 1),
              ]),
            ];
          }
          else {
            $probability_rows = [];
            foreach ($answer['probabilities'] as $option => $probability) {
              $label = $q['type'] === 'score' ? $option . ' — ' . $q['criteria'][$option] : QuestionDefinition::displayValue($q, (string) $option);
              $probability_rows[] = [
                ['data' => ['#plain_text' => $label]],
                round($probability * 100, 1) . '%',
              ];
            }
            $form['answers'][$id]['probabilities'] = [
              '#type' => 'table',
              '#header' => [
                $this->t('Category or level'),
                $this->t('Probability'),
              ],
              '#rows' => $probability_rows,
            ];
          }
          $field = [
            '#title' => $this->t('@label — staff correction', [
              '@label' => $q['label'],
            ]),
            '#default_value' => $overrides[$id] ?? '',
            '#disabled' => !$can_review,
          ];
          if ($q['type'] === 'score') {
            $field += [
              '#type' => 'number',
              '#min' => 0,
              '#max' => count($q['criteria']) - 1,
              '#step' => 'any',
              '#description' => $this->t('Leave empty to retain the model result.'),
            ];
          }
          else {
            $options = $q['type'] === 'choice' ? array_map(static fn($key) => QuestionDefinition::displayValue($q, (string) $key), array_combine(array_keys($q['criteria']), array_keys($q['criteria']))) : [
              'yes' => $this->t('Oui'),
              'no' => $this->t('Non'),
            ];
            $field += [
              '#type' => 'select',
              '#options' => ['' => $this->t('Keep model result')] + $options,
            ];
          }
          $form['corrections'][$id] = $field;
        }
      }
      if ($record->get('reviewed')->value) {
        $form['reviewer'] = [
          '#plain_text' => $this->t('Reviewed by user @uid at @time UTC.', [
            '@uid' => $record->get('reviewer')->value,
            '@time' => gmdate('Y-m-d H:i:s', (int) $record->get('reviewed')->value),
          ]),
        ];
      }
    }
    else {
      $form['empty'] = [
        '#markup' => $this->t('This submission has not been analyzed.'),
      ];
      $can_review = FALSE;
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['review'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save corrections and mark reviewed'),
      '#access' => $can_review,
    ];
    $form['actions']['reanalyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reanalyze'),
      '#submit' => ['::reanalyze'],
      '#limit_validation_errors' => [],
      '#access' => $this->currentUser()->hasPermission('run vactory webform typesafe') && (bool) $this->manager->settings($webform),
    ];
    $form['help'] = [
      '#markup' => $this->t('Corrections are stored separately from model output. A successful reanalysis replaces them.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sid = $form_state->get('sid');
    $key = 'vactory_webform_typesafe:save:' . $sid;
    if (!$this->lock->acquire($key, 30)) {
      $this->messenger()->addError($this->t('Analysis is being updated. Reload and try again.'));
      return;
    }
    try {
      $record = $this->manager->load($sid);
      $submission = $this->entities->getStorage('webform_submission')->load($sid);
      if (!$record || !$submission || !$submission->access('view') || !$this->currentUser()->hasPermission('review vactory webform typesafe')) {
        throw new AccessDeniedHttpException();
      }
      $version = hash('sha256', json_encode([
        $record->get('overrides')->value,
        $record->get('reviewer')->value,
        $record->get('reviewed')->value,
        $record->get('review_status')->value,
      ]));
      if ($record->get('status')->value !== 'completed' || $record->get('generation')->value !== $form_state->getValue('analysis_generation') || $version !== $form_state->getValue('analysis_review_version') || ($this->manager->snapshot($submission)['fingerprint'] ?? '') !== $record->get('fingerprint')->value) {
        $this->messenger()->addError($this->t('The analysis changed. Reload before reviewing.'));
        return;
      }
      $corrections = array_filter($form_state->getValue('corrections', []), static fn($v) => $v !== '' && $v !== NULL);
      // Revalidate server-side against the stored question definitions.
      foreach ($corrections as $id => $value) {
        $q = $record->decoded('questions')[$id] ?? NULL;
        $valid = $q && match ($q['type']) {
          'choice' => array_key_exists($value, $q['criteria']),
          'score' => is_numeric($value) && $value >= 0 && $value <= count($q['criteria']) - 1,
          'noul' => in_array($value, ['yes', 'no'], TRUE),
          default => FALSE,
        };
        if (!$valid) {
          throw new \InvalidArgumentException('Invalid correction.');
        }
      }
      $record->set('overrides', json_encode($corrections, JSON_THROW_ON_ERROR))->set('review_status', 'reviewed')
        ->set('reviewer', $this->currentUser()->id())->set('reviewed', \Drupal::time()->getCurrentTime());
      $labels = [];
      foreach ($record->decoded('results')['answers'] as $id => $answer) {
        $labels[] = $id . ':' . ($corrections[$id] ?? QuestionDefinition::value($record->decoded('questions')[$id], $answer));
      }
      $record->set('labels', $labels)->save();
      $this->messenger()->addStatus($this->t('Classification reviewed.'));
    }
    finally {
      $this->lock->release($key);
    }
    $form_state->setRebuild();
  }

  /**
   * Queues a fresh analysis after checking submission access.
   */
  public function reanalyze(array &$form, FormStateInterface $form_state) {
    $submission = $this->entities->getStorage('webform_submission')->load($form_state->get('sid'));
    if (!$submission || !$submission->access('view') || !$this->currentUser()->hasPermission('run vactory webform typesafe')) {
      throw new AccessDeniedHttpException();
    }
    try {
      $queued = $this->manager->enqueue($submission, TRUE);
      $this->messenger()->addStatus($queued ? $this->t('Submission queued for analysis.') : $this->t('Nothing queued. Check selected fields, draft status and handler settings.'));
    }
    catch (\Throwable) {
      $this->messenger()->addError($this->t('Could not queue the submission. Check its selected input size and handler configuration.'));
    }
    $form_state->setRebuild();
  }

}
