<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UsernameController extends ControllerBase {
  /**
   * Get unique username from email.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getUniqueUsernameByEmail(Request $request) {
    $email = $request->query->get('email');
    if (empty($email)) {
      return new JsonResponse(['errors' => ['No email was provided']], 400);
    }

    if (!\Drupal::service('email.validator')->isValid($email)) {
      return new JsonResponse(['errors' => ['Email is not valid']], 400);
    }

    // Strip off everything after the @ sign.
    $new_name = preg_replace('/@.*$/', '', $email);
    // Clean up the username.
    $new_name = email_registration_cleanup_username($new_name);

    // Ensure whatever name we have is unique.
    $new_name = email_registration_unique_username($new_name);

    return new JsonResponse([
      'username' => $new_name,
    ]);
  }

}
