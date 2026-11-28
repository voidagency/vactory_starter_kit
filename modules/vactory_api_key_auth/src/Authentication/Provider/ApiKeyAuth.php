<?php

namespace Drupal\vactory_api_key_auth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * API key authentication provider.
 *
 * Not global: it applies only to routes that list api_key_auth in their
 * _auth option.
 */
class ApiKeyAuth implements AuthenticationProviderInterface {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a HTTP basic authentication provider object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $routeMatch
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Only apply this validation if request has a valid accept value.
    return $this->getKey($request) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $api_key = $this->findApiKey((string) $this->getKey($request));
    if (!$api_key) {
      return NULL;
    }
    if (empty($api_key->user_uuid)) {
      // A key without account authenticates nobody (Redmine #71343).
      return NULL;
    }
    $account = $this->entityTypeManager->getStorage('user')->load($api_key->user_uuid);
    // A blocked account is refused, as with a password.
    if (!$account || $account->isBlocked()) {
      return NULL;
    }
    return $account;
  }

  /**
   * Returns the API key entity that matches the given key.
   *
   * @param string $key
   *   Key sent by the client.
   *
   * @return \Drupal\vactory_api_key_auth\Entity\ApiKey|null
   *   The API key entity, or NULL.
   */
  public function findApiKey(string $key) {
    if ($key === '') {
      return NULL;
    }
    foreach ($this->entityTypeManager->getStorage('api_key')->loadMultiple() as $api_key) {
      if (is_string($api_key->key) && $api_key->key !== '' && hash_equals($api_key->key, $key)) {
        return $api_key;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setThrowable(new UnauthorizedHttpException('Invalid consumer origin.', $exception));

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Retrieves the key from the request.
   *
   * The key is read from the "apikey" header only: in a query string or a
   * form body it would end up in server logs, browser history and Referer
   * headers.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that the service will respond to.
   *
   * @return string|false
   *   The key, or FALSE when there is none.
   */
  public function getKey(Request $request) {
    // Exempt edit/delete form route.
    $route_name = $this->routeMatch->getRouteName();
    if (is_string($route_name) && strstr($route_name, 'entity.api_key')) {
      return FALSE;
    }
    return static::keyFromRequest($request);
  }

  /**
   * Returns the key of the "apikey" header, or FALSE.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string|false
   *   The key, or FALSE when there is none.
   */
  public static function keyFromRequest(Request $request) {
    $key = $request->headers->get('apikey');
    return is_string($key) && $key !== '' ? $key : FALSE;
  }

}
