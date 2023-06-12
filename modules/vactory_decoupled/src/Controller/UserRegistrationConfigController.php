<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserRegistrationConfigController extends ControllerBase {

  /**
   * Get account registration config.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getAccountRegistrationConfig(Request $request) {
    $config = \Drupal::config('user_registrationpassword.settings');
    return new JsonResponse([
      'user_registration_password' => $config->get('registration') ?? 'with-pass',
    ]);
  }

}
