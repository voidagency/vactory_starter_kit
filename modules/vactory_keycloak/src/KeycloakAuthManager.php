<?php

namespace Drupal\vactory_keycloak;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\social_auth\User\SocialAuthUser;
use Drupal\social_auth\User\SocialAuthUserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains all the logic for Keycloak OAuth2 authentication.
 */
class KeycloakAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger_factory, RequestStack $request_stack) {
    parent::__construct($configFactory->get('vactory_keycloak.settings'), $logger_factory, $request_stack->getCurrentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(): void {
    if ($code = $this->request->query->get('code')) {
      try {
        $this->setAccessToken($this->client->getAccessToken('authorization_code', ['code' => $code]));
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('vactory_keycloak')
          ->error('There was an error during authentication. Exception: ' . $e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo(): ?SocialAuthUserInterface {
    if (!$this->user && $access_token = $this->getAccessToken()) {
      /** @var \Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner $owner */
      $owner = $this->client->getResourceOwner($access_token);
      $this->user = new SocialAuthUser(
        (string) ($owner->getName() ?: $owner->getUsername()),
        (string) $owner->getId(),
        (string) $access_token,
        $owner->getEmail(),
        NULL,
        $this->getExtraDetails()
      );
      $this->user->setFirstName($owner->getFirstName());
      $this->user->setLastName($owner->getLastName());
    }

    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl(): string {
    $scopes = ['profile'];

    $extra_scopes = $this->getScopes();
    if ($extra_scopes) {
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint(string $method, string $path, ?string $domain = NULL, array $options = []): mixed {
    // Keycloak offers only 'login with' services.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->client->getState();
  }

}
