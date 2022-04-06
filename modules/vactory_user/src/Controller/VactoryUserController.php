<?php

namespace Drupal\vactory_user\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller UserApiController.
 */
class VactoryUserController extends ControllerBase {

  /**
   * @return JsonResponse
   */
  public function deleteAccount() {
    $account = \Drupal::currentUser();
    $user = User::load($account->id());
    $user->block();
    $user->save();
    return new JsonResponse([
    ], 204);
  }

}
