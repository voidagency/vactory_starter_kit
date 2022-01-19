<?php

namespace Drupal\vactory_email_ajax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Vactory Email AJAX routes.
 */
class VactoryEmailAjaxController extends ControllerBase {

  /**
   * User storage.
   */
  protected $userStorage;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->userStorage = $container->get('entity_type.manager')->getStorage('user');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function validate() {
    $email = \Drupal::request()->request->get('email');
    $response = [];
    $response['is_valid'] = TRUE;
    if (!empty($email)) {
      $user = $this->userStorage->loadByProperties(['mail' => $email]);
      if (!empty($user)) {
        $response['is_valid'] = FALSE;
      }
    }
    return new JsonResponse($response, 200);
  }

}
