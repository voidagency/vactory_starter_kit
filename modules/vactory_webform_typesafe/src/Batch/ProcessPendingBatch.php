<?php

namespace Drupal\vactory_webform_typesafe\Batch;

use Drupal\Core\Queue\DelayedRequeueException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Processes pending analyses through the same coordinator used by cron.
 */
final class ProcessPendingBatch {

  /**
   * Checks current permission and access to the Webform's results.
   */
  public static function checkAccess(string $webform_id): void {
    $account = \Drupal::currentUser();
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
    if (!$account->hasPermission('run vactory webform typesafe') || !$account->hasPermission('view vactory webform typesafe') || !$webform || !$webform->access('submission_view_any', $account)) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Processes one pending analysis, retaining its queue item for cron retries.
   */
  public static function process(string $webform_id, int $sid, array &$context): void {
    self::checkAccess($webform_id);
    $context['results'] += ['completed' => 0, 'pending' => 0, 'other' => 0];
    $manager = \Drupal::service('vactory_webform_typesafe.manager');
    $record = $manager->load($sid);
    if (!$record || $record->get('webform')->value !== $webform_id || $record->get('status')->value !== 'pending') {
      return;
    }
    $submission = $record->get('submission')->entity;
    if (!$submission || !$submission->access('view')) {
      return;
    }
    $generation = $record->get('generation')->value;
    try {
      $manager->process(['sid' => $sid, 'generation' => $generation]);
    }
    catch (DelayedRequeueException) {
      // Cron retains responsibility for locked items and retryable failures.
    }
    $record = $manager->load($sid);
    $status = $record && $record->get('generation')->value === $generation ? $record->get('status')->value : 'other';
    $context['results'][match ($status) {
      'completed' => 'completed',
      'pending', 'processing' => 'pending',
      default => 'other',
    }]++;
    $context['message'] = t('Soumission @sid traitée.', ['@sid' => $sid]);
  }

  /**
   * Reports completion without exposing API errors or submission contents.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    if (!$success) {
      \Drupal::messenger()->addError(t('Le traitement a été interrompu. Cron pourra traiter les soumissions encore en attente.'));
      return;
    }
    \Drupal::messenger()->addStatus(t('Terminées : @completed. En attente de cron ou d’une nouvelle tentative : @pending. En échec ou obsolètes : @other. Actualisez les résultats pour consulter leur état actuel.', [
      '@completed' => $results['completed'] ?? 0,
      '@pending' => $results['pending'] ?? 0,
      '@other' => $results['other'] ?? 0,
    ]));
  }

}
