<?php

namespace Drupal\vactory_quiz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Quiz answers modal controller class.
 */
class QuizApiController extends ControllerBase {

  /**
   * Posts items in agenda.
   */
  public function postQuiz(Request $request) {

    $req = json_decode($request->getContent());

    if (!isset($req) || empty($req)) {
      return new JsonResponse([
        'message' => $this->t('Empty PARAMS!'),
        'status' => FALSE,
        'req' => $req,
      ], 400);
    }
    // Get current user.
    $account = \Drupal::currentUser();

    $validateQuizId = isset($req->quiz_nid) && filter_var($req->quiz_nid, FILTER_VALIDATE_INT);
    $validateUserMark = isset($req->user_mark) && (filter_var($req->user_mark, FILTER_VALIDATE_INT) === 0 || filter_var($req->user_mark, FILTER_VALIDATE_INT));
    $validatePerfectMark = isset($req->perfect_mark) && filter_var($req->perfect_mark, FILTER_VALIDATE_INT);
    $validateUserAnswers = isset($req->user_answers) && is_array(json_decode($req->user_answers, TRUE));

    if ($validateQuizId && $validateUserMark && $validatePerfectMark && $validateUserAnswers) {
      \Drupal::database()->insert('vactory_quiz_history')
        ->fields([
          'uid' => $account->id(),
          'quiz_nid' => $req->quiz_nid,
          'user_mark' => $req->user_mark,
          'perfect_mark' => $req->perfect_mark,
          'user_answers' => $req->user_answers,
          'time' => \Drupal::time()->getCurrentTime(),
        ])
        ->execute();
      return new JsonResponse([
        'message' => $this->t('Saved'),
        'status' => TRUE,
        'req' => $req,
      ]);
    }
    else {
      return new JsonResponse([
        'message' => $this->t('Invalid PARAMS!'),
        'status' => FALSE,
        'req' => $req,
      ], 400);
    }
  }

  /**
   * Get current user quiz history.
   */
  public function getQuizHistory() {
    // Get current user.
    $account = \Drupal::currentUser();
    $query = "SELECT * FROM vactory_quiz_history where uid = :uid";
    $searches = \Drupal::database()->query($query, [
      ':uid' => $account->id(),
    ]);
    if (isset($searches) and !empty($searches)) {
      $result = [];
      foreach ($searches as $key => $search) {
        $result[$key]['uid'] = (int) $search->uid;
        $result[$key]['quiz_nid'] = (int) $search->quiz_nid;
        $result[$key]['user_mark'] = (int) $search->user_mark;
        $result[$key]['perfect_mark'] = (int) $search->perfect_mark;
        $result[$key]['user_answers'] = json_decode($search->user_answers);
        $result[$key]['time'] = (int) $search->time;
      }
      return new JsonResponse([
        'data' => $result,
      ]);
    }
    else {
      return new JsonResponse([
        'data' => [],
      ]);
    }
  }

}
