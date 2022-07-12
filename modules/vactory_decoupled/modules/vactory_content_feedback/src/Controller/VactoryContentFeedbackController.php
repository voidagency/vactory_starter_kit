<?php

namespace Drupal\vactory_content_feedback\Controller;

use Drupal\admin_feedback\Controller\AdminFeedbackController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The VactoryContentFeedbackController class.
 */
class VactoryContentFeedbackController extends AdminFeedbackController
{

  /**
   * Function for updating rows in database.
   */
  public function updateFeedback($feedback_id = NULL, $feedback_message = NULL)
  {
    if (!empty($feedback_id)) {
      $feedback_id = base64_decode($feedback_id);
    }

    if ($feedback_message != NULL && !empty($feedback_message)) {
      try {
        $affected_rows = $this->database->update('admin_feedback')
          ->fields([
            'feedback_message' => $feedback_message,
          ])
          ->condition('id', $feedback_id)
          ->execute();
        return $affected_rows;
      } catch (\Exception $e) {
        \Drupal::logger('vactory_content_feedback')->error($e->getMessage());
        return null;
      }
    }
  }

  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request)
  {
    $data = json_decode($request->getContent());

    // Basic check for Feedback ID.
    if (empty($data->feedback_id)) {
      return new JsonResponse([
        'message' => t('Missing id'),
      ], 400);
    }

    if (empty($data->feedback_message)) {
      return new JsonResponse([
        'message' => t('Missing message'),
      ], 400);
    }

    $feedback_id = $data->feedback_id;
    $feedback_message = $data->feedback_message;

    $result = $this->updateFeedback($feedback_id, $feedback_message);

    if ($result) {
      return new JsonResponse([
        'status' => TRUE,
        'messages' => $this->t('Feedback updated successfully')
      ], 200);
    } else {
      return new JsonResponse([
        'status' => FALSE,
        'messages' => $this->t('Error updating feedback')
      ], 400);
    }
  }
}
