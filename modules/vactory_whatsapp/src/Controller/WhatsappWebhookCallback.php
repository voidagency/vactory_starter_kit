<?php

namespace Drupal\vactory_whatsapp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vactory whatsapp webhook callback.
 */
class WhatsappWebhookCallback extends ControllerBase {

  /**
   * Builds the response.
   */
  public function handler(Request $request) {
    $verify_token = 'vactory';
    $mode = $request->query->get('hub.mode');
    $token = $request->query->get('hub.verify_token');
    $challenge = $request->query->get('hub.challenge');
    \Drupal::logger('vactory whatsapp')->notice("Challenge: $challenge");
    \Drupal::logger('vactory whatsapp')->notice("Token: $token");
    \Drupal::logger('vactory whatsapp')->notice("Mode: $mode");
    if ($verify_token === $token) {
      return new JsonResponse($challenge, 200);
    }
    return new JsonResponse('', 403);
  }

}
