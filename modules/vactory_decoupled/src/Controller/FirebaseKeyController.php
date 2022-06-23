<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Firebase Cloud Messaging key controller.
 */
class FirebaseKeyController extends ControllerBase {

  /**
   * Get firebqse key from state.
   */
  public function getFirebaseKey() {
    $state = \Drupal::state();
    $key = $state->get('firebase_key', '');
    return new JsonResponse([
      'key' => $key,
    ]);
  }

}
