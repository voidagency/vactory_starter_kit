<?php

namespace Drupal\vactory_decoupled;

use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider as BaseSimpleOauthAuthenticationProvider;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Lcobucci\JWT\Token\DataSet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Drupal\user\Entity\User;
use Drupal\Core\Site\Settings;

/**
 * Simple oauth authentication provider class.
 */
class SimpleOauthAuthenticationProvider extends BaseSimpleOauthAuthenticationProvider {

  /**
   * The Social Auth user manager.
   *
   * @var \Drupal\social_auth\User\UserManager
   */
  protected $userManager;

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   */
  public function authenticate(Request $request) {
    if ($request->headers->has("X-Auth-Provider")) {
      $provider = $request->headers->get("X-Auth-Provider");
      $authorization = $request->headers->get('authorization');
      $jwt = trim(str_replace('Bearer ', '', $authorization));
      $this->userManager = \Drupal::service("vactory_decoupled.user_manager");
      $this->cache = \Drupal::cache('vactory_decoupled_oauth');
      $this->moduleHandler = \Drupal::service("module_handler");

      if ($provider === "keycloak") {
        return $this->authenticateKeycloak($jwt);
      }
      elseif ($provider === "facebook") {
        return $this->authenticateFacebook($jwt);
      }
      elseif ($provider === "google") {
        return $this->authenticateGoogle($jwt);
      }
    }

    return parent::authenticate($request);
  }

  /**
   * Authenticate google.
   */
  private function authenticateGoogle(string $jwt) {
    $cacheKey = "google:" . $jwt;
    if ($cache = $this->cache->get($cacheKey)) {
      $payload = $cache->data;
    }
    else {
      $result = \Drupal::httpClient()
        ->get("https://openidconnect.googleapis.com/v1/userinfo", [
          'headers' => [
            'Authorization' => "Bearer " . $jwt,
          ],
        ]);
      $payload = json_decode((string) $result->getBody(), TRUE);
      $ten_minutes = 60 * 10;
      $this->cache->set($cacheKey, $payload, $this->getRequestTime() + $ten_minutes);
    }

    $id = $payload["sub"];
    $name = $payload["name"];
    $first_name = $payload["given_name"];
    $last_name = $payload["family_name"] ?? "";
    $email = $payload["email"];
    $picture_url = $payload["picture"];
    $username = (!empty($email)) ? $email : "{$id}@google.com";
    $mail = (!empty($email)) ? $email : "{$id}@google.com";

    $user = user_load_by_name($username);
    if (empty($user)) {
      // Try loading user by mail.
      $user = user_load_by_mail($mail);
    }
    if (!$user) {
      $values = [
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
        'field_first_name' => $first_name,
        'field_last_name' => $last_name,
      ];

      if (!empty($picture_url) && $this->userManager->userPictureEnabled()) {
        $file = $this->userManager->downloadProfilePic($picture_url, $id);
        if ($file) {
          $values['user_picture'] = $file->id();
        }
      }

      $user = User::create($values);
      $user->save();
    }
    return $user;
  }

  /**
   * Authenticate facebook.
   */
  private function authenticateFacebook(string $jwt) {
    $cacheKey = "facebook:" . $jwt;
    if ($cache = $this->cache->get($cacheKey)) {
      $payload = $cache->data;
    }
    else {
      $result = \Drupal::httpClient()->get("https://graph.facebook.com/me", [
        'query' => [
          'access_token' => $jwt,
          'fields' => 'id,name,email,picture',
        ],
      ]);
      $payload = json_decode((string) $result->getBody(), TRUE);
      $ten_minutes = 60 * 10;
      $this->cache->set($cacheKey, $payload, $this->getRequestTime() + $ten_minutes);
    }

    $id = $payload["id"];
    $name = $payload["name"];
    [$first_name, $last_name] = array_pad(explode(' ', $name), 4, '');
    $email = $payload["email"];
    $picture_url = $payload["picture"];
    $username = (!empty($email)) ? $email : "{$id}@facebook.com";
    $mail = (!empty($email)) ? $email : "{$id}@facebook.com";

    $user = user_load_by_name($username);
    if (empty($user)) {
      // Try loading user by mail.
      $user = user_load_by_mail($mail);
    }
    if (!$user) {
      $values = [
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
        'field_first_name' => $first_name,
        'field_last_name' => $last_name,
      ];

      if (isset($picture_url["data"]["url"]) && $this->userManager->userPictureEnabled()) {
        $file = $this->userManager->downloadProfilePic($picture_url["data"]["url"], $id);
        if ($file) {
          $values['user_picture'] = $file->id();
        }
      }

      $user = User::create($values);
      $user->save();
    }
    return $user;
  }

  /**
   * Authenticate keycloak.
   *
   * The token must be signed by one of the realm keys (JWKS), issued by the
   * configured realm and currently valid (exp/nbf/iat). Required settings:
   * - KEYCLOAK_ISSUER: realm issuer, e.g. https://sso.example.com/realms/foo.
   * Optional settings:
   * - KEYCLOAK_JWKS_URI: defaults to {issuer}/protocol/openid-connect/certs.
   * - KEYCLOAK_AUDIENCE: client id expected in "aud" or "azp".
   */
  private function authenticateKeycloak(string $jwt) {
    $keycloak_issuer = rtrim((string) Settings::get('KEYCLOAK_ISSUER', ''), '/');
    if (empty($keycloak_issuer)) {
      // Keycloak authentication is disabled unless explicitly configured.
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Keycloak authentication is not configured');
    }

    try {
      $claims = $this->decodeKeycloakToken($jwt, $keycloak_issuer, FALSE);
    }
    catch (\UnexpectedValueException $exception) {
      // Unknown "kid": keys may have been rotated, refresh JWKS once.
      if (strpos($exception->getMessage(), '"kid"') === FALSE) {
        throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
      }
      try {
        $claims = $this->decodeKeycloakToken($jwt, $keycloak_issuer, TRUE);
      }
      catch (\Exception $e) {
        throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
      }
    }
    catch (\Exception $exception) {
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
    }

    if (($claims['iss'] ?? NULL) !== $keycloak_issuer) {
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
    }

    $audience = Settings::get('KEYCLOAK_AUDIENCE', '');
    if (!empty($audience)) {
      $aud = (array) ($claims['aud'] ?? []);
      if (!in_array($audience, $aud, TRUE) && ($claims['azp'] ?? NULL) !== $audience) {
        throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
      }
    }

    $username = $claims['preferred_username'] ?? NULL;
    if (!is_string($username) || $username === '') {
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
    }
    $email_verified = !empty($claims['email_verified']);
    $mail = !empty($claims['email']) ? $claims['email'] : $username . "@keycloak.com";

    $user = user_load_by_name($username);
    if (empty($user) && $email_verified && !empty($claims['email'])) {
      // Only trust the email for account linking when Keycloak verified it.
      $user = user_load_by_mail($mail);
    }
    if (!$user) {
      $values = [
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
      ];

      $user = User::create($values);
      $user->save();
    }

    // Never allow the super admin account or blocked users through Keycloak.
    if ((int) $user->id() === 1 || $user->isBlocked()) {
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access denied');
    }

    // Keep passing a lcobucci DataSet for backward compatibility with
    // hook_simple_oauth_authentication_keycloak_alter() implementations.
    $claims_set = new DataSet($claims, $jwt);
    $this->moduleHandler->alter('simple_oauth_authentication_keycloak', $user, $claims_set, $jwt);
    return $user;
  }

  /**
   * Verifies a Keycloak JWT against the realm JWKS and returns its claims.
   *
   * @throws \Exception
   *   When the token signature, algorithm or time claims are invalid.
   */
  private function decodeKeycloakToken(string $jwt, string $issuer, bool $refresh): array {
    $jwks_uri = Settings::get('KEYCLOAK_JWKS_URI', $issuer . '/protocol/openid-connect/certs');
    $cache_key = 'keycloak_jwks:' . $jwks_uri;

    $jwks = NULL;
    if ($refresh) {
      // Throttle forced refreshes so bogus "kid" values can't hammer Keycloak.
      if ($this->cache->get($cache_key . ':refreshed')) {
        throw new \UnexpectedValueException('JWKS refresh throttled');
      }
      $this->cache->set($cache_key . ':refreshed', TRUE, $this->getRequestTime() + 60);
    }
    elseif ($cache = $this->cache->get($cache_key)) {
      $jwks = $cache->data;
    }
    if (empty($jwks)) {
      $response = \Drupal::httpClient()->get($jwks_uri, ['timeout' => 5]);
      $jwks = json_decode((string) $response->getBody(), TRUE);
      if (empty($jwks['keys']) || !is_array($jwks['keys'])) {
        throw new \UnexpectedValueException('Invalid JWKS');
      }
      // Only keep signing keys, Keycloak also publishes encryption keys.
      $jwks['keys'] = array_values(array_filter($jwks['keys'], function ($key) {
        return ($key['use'] ?? 'sig') === 'sig';
      }));
      $this->cache->set($cache_key, $jwks, $this->getRequestTime() + 3600);
    }

    // Asymmetric keys only: parseKeySet binds each key to its algorithm, so
    // "alg: none" and HS256 key confusion are rejected by JWT::decode().
    $keys = JWK::parseKeySet($jwks, 'RS256');
    JWT::$leeway = 30;
    return (array) json_decode(json_encode(JWT::decode($jwt, $keys)), TRUE);
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
