<?php

namespace Drupal\vactory_form_autosave\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Form autosave service.
 */
class VactoryFormAutosaveManager {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritDoc}
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Update form draft.
   */
  public function updateFormDraft($data, $form_id, AccountProxyInterface $account, $session_id = NULL) {
    $query = "SELECT * FROM vactory_form_autosave_storage where uid = :uid and form_id = :form_id";
    $query_params = [
      ':uid' => $account->id(),
      ':form_id' => $form_id,
    ];
    if ($account->isAnonymous()) {
      $query = "SELECT * FROM vactory_form_autosave_storage where session_id = :session_id and form_id = :form_id";
      unset($query_params[':uid']);
      $query_params[':session_id'] = $session_id;
    }
    $results = $this->database->query($query, $query_params)->fetchAll();
    if (empty($results)) {
      // Create new draft.
      $this->database->insert('vactory_form_autosave_storage')
        ->fields([
          'uid' => $account->id(),
          'form_id' => $form_id,
          'session_id' => $session_id,
          'data' => $data,
          'created' => $this->time->getCurrentTime(),
        ])
        ->execute();
      return 1;
    }

    // Update existing draft.
    $result = $results[0];
    $this->database->update('vactory_form_autosave_storage')
      ->fields([
        'data' => $data,
        'created' => $this->time->getCurrentTime(),
      ])
      ->condition('id', $result->id)
      ->execute();
    return 2;
  }

  /**
   * Get form draft by form ID, uid and session id.
   */
  public function getFormDraft($form_id, AccountProxyInterface $account, $session_id = NULL) {
    $data = FALSE;
    if (!empty($form_id)) {
      $query = "SELECT * FROM vactory_form_autosave_storage WHERE uid = :uid AND form_id = :form_id";
      $query_params = [
        ':uid' => $account->id(),
        ':form_id' => $form_id,
      ];
      if ($account->isAnonymous() && empty($session_id)) {
        return FALSE;
      }
      if ($account->isAnonymous()) {
        $query = "SELECT * FROM vactory_form_autosave_storage WHERE session_id = :session_id AND form_id = :form_id";
        unset($query_params[':uid']);
        $query_params[':session_id'] = $session_id;
      }
      $results = $this->database->query($query, $query_params)->fetchAll();
      if (!empty($results)) {
        $data = $results[0]->data;
      }
    }
    return $data;
  }

  /**
   * Purge expired form drafts.
   */
  public function purgeFormDraft($lifetime) {
    $current_time = $this->time->getCurrentTime();
    $query = "SELECT id FROM vactory_form_autosave_storage WHERE TIMESTAMPDIFF(DAY, FROM_UNIXTIME(created), FROM_UNIXTIME(:current_time)) > :life_time";
    $query_params = [
      ':current_time' => $current_time,
      ':life_time' => $lifetime,
    ];
    $drafts = $this->database->query($query, $query_params)->fetchAll();
    if (!empty($drafts)) {
      $drafts_ids = array_map(function ($draft) {
        return $draft->id;
      },$drafts);
      $this->database->delete('vactory_form_autosave_storage')
        ->condition('id', $drafts_ids, 'IN')
        ->execute();
    }
  }

}
