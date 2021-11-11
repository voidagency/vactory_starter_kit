<?php

namespace Drupal\vactory_keycloak\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\vactory_keycloak\KeycloakAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth Keycloak routes.
 */
class KeycloakAuthController extends OAuth2ControllerBase {

  /**
   * KeycloakAuthController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of vactory_keycloak network plugin.
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   Used to manage user authentication/registration.
   * @param \Drupal\vactory_keycloak\KeycloakAuthManager $keycloak_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   The Social Auth Data handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Used to handle metadata for redirection to authentication URL.
   */
  public function __construct(MessengerInterface $messenger,
                              NetworkManager $network_manager,
                              UserAuthenticator $user_authenticator,
                              KeycloakAuthManager $keycloak_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler,
                              RendererInterface $renderer) {

    parent::__construct('Social Auth Keycloak', 'vactory_keycloak',
                        $messenger, $network_manager, $user_authenticator,
      $keycloak_manager, $request, $data_handler, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('vactory_keycloak.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer')
    );
  }

  /**
   * Response for path 'user/login/keycloak/callback'.
   *
   * Keycloak returns the user here after user has authenticated.
   */
  public function callback() {

    // Checks if there was an authentication error.
    $redirect = $this->checkAuthError();
    if ($redirect) {
      return $redirect;
    }

    /* @var \pviojo\OAuth2\Client\Provider\KeycloakResourceOwner|null $profile */
    $profile = $this->processCallback();

    // If authentication was successful.
    if ($profile) {

      // Check for email.
      // @todo: we do not always have an email
      // @todo: generate a custom one for this case.
      if (!$profile->getEmail()) {
        $this->messenger->addError($this->t('Keycloak authentication failed. This site requires permission to get your email address.'));

        return $this->redirect('user.login');
      }

      // If user information could be retrieved.
      return $this->userAuthenticator->authenticateUser($profile->getName(),
                                                        $profile->getEmail(),
                                                        $profile->getId(),
                                                        $this->providerManager->getAccessToken());
    }

    return $this->redirect('user.login');
  }

}
