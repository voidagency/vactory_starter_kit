<?php

namespace Drupal\vactory_keycloak\Controller;

use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Social Auth Keycloak routes.
 */
class KeycloakAuthController extends OAuth2ControllerBase {

  /**
   * Response for path 'user/login/keycloak'.
   *
   * Redirects the user to Keycloak for authentication.
   */
  public function redirectToKeycloak(): Response {
    return $this->redirectToProvider($this->getNetwork());
  }

  /**
   * Response for path 'user/login/keycloak/callback'.
   *
   * Keycloak returns the user here after user has authenticated.
   */
  public function keycloakCallback(): RedirectResponse {
    // Checks if there was an authentication error. OAuth2ControllerBase
    // ::checkAuthError() is not used, it fatals on social_auth 4.1.
    if ($this->request->getCurrentRequest()->query->has('error')) {
      $this->messenger->addError($this->t('You could not be authenticated.'));
      return $this->redirect('user.login');
    }

    return $this->callback($this->getNetwork());
  }

  /**
   * Gets the Keycloak network plugin.
   *
   * @return \Drupal\social_auth\Plugin\Network\NetworkInterface
   *   The network plugin.
   */
  protected function getNetwork() {
    return $this->networkManager->createInstance('vactory_keycloak');
  }

}
