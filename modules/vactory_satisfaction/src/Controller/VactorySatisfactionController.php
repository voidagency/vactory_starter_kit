<?php

namespace Drupal\vactory_satisfaction\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

/**
 * Vactory Satisfaction controller.
 */
class VactorySatisfactionController extends ControllerBase {

  /**
   * Stores user response in the database.
   */
  public function storeResponse(Request $request) {
    // Get the POST data.
    $data = json_decode($request->getContent(), TRUE);

    // Validation.
    if (!isset($data['nid']) || !isset($data['response'])) {
      return new JsonResponse(['error' => 'Invalid request. Missing nid, or response.'], 400);
    }

    // Extract the data.
    $nid = (int) $data['nid'];
    $response = json_encode($data['response']);

    $uid = \Drupal::currentUser()->id();

    // Insert the data into the database.
    try {
      \Drupal::database()->insert('vactory_satisfaction')
        ->fields([
          'uid' => $uid,
          'nid' => $nid,
          'response' => $response,
        ])
        ->execute();

      return new JsonResponse(['message' => 'Response stored successfully'], 200);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Failed to store the response.'], 500);
    }
  }

}
