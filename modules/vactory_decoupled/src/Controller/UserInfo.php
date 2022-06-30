<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;

/**
 * Controller for the User Info endpoint.
 * @see ../src/Routing/RouteSubscriber.php
 */
class UserInfo extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function handle() {
    $user = User::load($this->currentUser()->id());
    $data = get_oauth_user_infos($user);
    $data["sub"] = $user->id();
    return JsonResponse::create($data);
  }

}
