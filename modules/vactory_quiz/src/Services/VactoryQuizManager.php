<?php

namespace Drupal\vactory_quiz\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Vactory quiz manager service class.
 */
class VactoryQuizManager {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public function getPerfectMark($quiz_nid) {
    $perfect_mark = 0;
    $quiz = \Drupal::entityTypeManager()->getStorage('node')
      ->load($quiz_nid);
    if ($quiz instanceof NodeInterface && $quiz->bundle() === 'vactory_quiz') {
      $questions = $quiz->get('field_quiz_questions')->getValue();
      foreach ($questions as $question) {
        $perfect_mark += (int) $question['question_reward'];
      }
    }

    return $perfect_mark;
  }

  /**
   * Get quiz attempt history for the given user.
   */
  public function getQuizUserAttemptHistory($quiz_id, $user_id) {
    $query = "SELECT * FROM vactory_quiz_history where uid = :uid and quiz_nid = :nid";
    $searches = $this->database->query($query, [
      ':uid' => $user_id,
      ':nid' => $quiz_id,
    ]);
    $result = [];
    if (isset($searches) and !empty($searches)) {
      foreach ($searches as $search) {
        $result['uid'] = (int) $search->uid;
        $result['quiz_nid'] = (int) $search->quiz_nid;
        $result['user_mark'] = (int) $search->user_mark;
        $result['perfect_mark'] = (int) $search->perfect_mark;
        $result['user_answers'] = Json::decode($search->user_answers);
        $result['time'] = (int) $search->time;
        $result['certificat'] = $search->certificat;
        $result['certificat_time'] = $search->certificat_time;
      }
    }
    return $result;
  }

  /**
   * Get all quiz ids passed by the given user.
   */
  public function getAllPassedQuiz($user_id) {
    $query = "SELECT * FROM vactory_quiz_history where uid = :uid";
    $searches = $this->database->query($query, [
      ':uid' => $user_id,
    ]);
    $results = [];
    if (isset($searches) and !empty($searches)) {
      foreach ($searches as $search) {
        $results[] = [
          'uid' => (int) $search->uid,
          'quiz_nid' => (int) $search->quiz_nid,
          'user_mark' => (int) $search->user_mark,
          'perfect_mark' => (int) $search->perfect_mark,
          'user_answers' => Json::decode($search->user_answers),
          'time' => (int) $search->time,
          'certificat' => $search->certificat,
          'certificat_time' => (int) $search->certificat_time,
        ];
      }
    }
    return $results;
  }


  /**
   * Update user attempt history if exist or create it.
   */
  public function updateUserAttemptHistory($uid, $quiz_nid, $user_mark, $user_answers, $certificat = '', $certificat_time = NULL) {
    $query = "SELECT * FROM vactory_quiz_history where uid = :uid and quiz_nid = :nid";
    $quiz_perfect_mark = $this->getPerfectMark($quiz_nid);
    $searches = $this->database->query($query, [
      ':uid' => $uid,
      ':nid' => $quiz_nid,
    ]);
    $result = [];
    if (isset($searches) and !empty($searches)) {
      foreach ($searches as $search) {
        $result['uid'] = (int) $search->uid;
        $result['perfect_mark'] = (int) $search->perfect_mark;
      }
    }
    if (empty($result)) {
      $this->database->insert('vactory_quiz_history')
        ->fields([
          'uid' => $uid,
          'quiz_nid' => $quiz_nid,
          'user_mark' => $user_mark,
          'perfect_mark' => $user_mark > $quiz_perfect_mark ? $quiz_perfect_mark : $user_mark,
          'user_answers' => Json::encode($user_answers),
          'time' => \Drupal::time()->getCurrentTime(),
          'certificat' => $certificat,
          'certificat_time' => $certificat_time,
        ])
        ->execute();
    }
    else {
      $user_perfect_mark = $user_mark > $result['perfect_mark'] ? $user_mark : $result['perfect_mark'];
      $user_perfect_mark = $user_perfect_mark > $quiz_perfect_mark ? $quiz_perfect_mark : $user_perfect_mark;
      $this->database->update('vactory_quiz_history')
        ->fields([
          'uid' => $uid,
          'quiz_nid' => $quiz_nid,
          'user_mark' => $user_mark,
          'perfect_mark' => $user_perfect_mark,
          'user_answers' => Json::encode($user_answers),
          'time' => \Drupal::time()->getCurrentTime(),
          'certificat' => $certificat,
          'certificat_time' => $certificat_time,
        ])
        ->condition('uid', $uid)
        ->condition('quiz_nid', $quiz_nid)
        ->execute();
    }
  }

}
