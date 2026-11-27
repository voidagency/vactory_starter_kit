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
   *
   * The access token is sent to Google's userinfo endpoint. An existing
   * account is linked only by an email Google verified (Redmine #71366).
   * Optional setting:
   * - GOOGLE_CLIENT_ID: client id (string or list) the token must be issued
   *   for, checked with Google's tokeninfo endpoint.
   */
  private function authenticateGoogle(string $jwt) {
    $realm = 'Bearer realm="google"';
    $payload = $this->socialPayload('google:' . hash('sha256', $jwt), $realm, function () use ($jwt) {
      $client_ids = array_filter((array) Settings::get('GOOGLE_CLIENT_ID', []));
      if ($client_ids) {
        $info = $this->fetchJson('https://oauth2.googleapis.com/tokeninfo', [
          'query' => ['access_token' => $jwt],
        ]);
        $audiences = array_filter([$info['aud'] ?? NULL, $info['azp'] ?? NULL]);
        if (!array_intersect($audiences, $client_ids)) {
          return NULL;
        }
      }
      return $this->fetchJson('https://openidconnect.googleapis.com/v1/userinfo', [
        'headers' => ['Authorization' => 'Bearer ' . $jwt],
      ]);
    });

    $id = (string) ($payload['sub'] ?? '');
    $email_verified = filter_var($payload['email_verified'] ?? FALSE, FILTER_VALIDATE_BOOLEAN);
    $email = $email_verified ? (string) ($payload['email'] ?? '') : '';
    return $this->socialUser('google', $id, $email, $realm, [
      'field_first_name' => $payload['given_name'] ?? '',
      'field_last_name' => $payload['family_name'] ?? '',
    ], $payload['picture'] ?? NULL);
  }

  /**
   * Authenticate facebook.
   *
   * The access token is sent to the Graph API (/me). Facebook only returns a
   * confirmed email. Optional settings:
   * - FACEBOOK_APP_ID and FACEBOOK_APP_SECRET: the token must be issued for
   *   this application, checked with the Graph API debug_token endpoint.
   */
  private function authenticateFacebook(string $jwt) {
    $realm = 'Bearer realm="facebook"';
    $payload = $this->socialPayload('facebook:' . hash('sha256', $jwt), $realm, function () use ($jwt) {
      $app_id = (string) Settings::get('FACEBOOK_APP_ID', '');
      $app_secret = (string) Settings::get('FACEBOOK_APP_SECRET', '');
      if ($app_id !== '' && $app_secret !== '') {
        $debug = $this->fetchJson('https://graph.facebook.com/debug_token', [
          'query' => [
            'input_token' => $jwt,
            'access_token' => $app_id . '|' . $app_secret,
          ],
        ]);
        if (empty($debug['data']['is_valid']) || (string) ($debug['data']['app_id'] ?? '') !== $app_id) {
          return NULL;
        }
      }
      return $this->fetchJson('https://graph.facebook.com/me', [
        'query' => [
          'access_token' => $jwt,
          'fields' => 'id,name,email,picture',
        ],
      ]);
    });

    $id = (string) ($payload['id'] ?? '');
    [$first_name, $last_name] = array_pad(explode(' ', (string) ($payload['name'] ?? ''), 2), 2, '');
    return $this->socialUser('facebook', $id, (string) ($payload['email'] ?? ''), $realm, [
      'field_first_name' => $first_name,
      'field_last_name' => $last_name,
    ], $payload['picture']['data']['url'] ?? NULL);
  }

  /**
   * Returns the provider payload for a token, cached ten minutes.
   *
   * @param string $cache_key
   *   Cache id of the token (a hash: the token itself is never stored).
   * @param string $realm
   *   WWW-Authenticate value of a refusal.
   * @param callable $fetch
   *   Returns the payload, or NULL when the token is not for this site.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   */
  private function socialPayload(string $cache_key, string $realm, callable $fetch): array {
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }
    try {
      $payload = $fetch();
    }
    catch (\Throwable $e) {
      // Invalid or expired token, or the provider is unreachable.
      $payload = NULL;
    }
    if (!is_array($payload)) {
      throw new UnauthorizedHttpException($realm, 'Access token could not be verified');
    }
    $this->cache->set($cache_key, $payload, $this->getRequestTime() + 600);
    return $payload;
  }

  /**
   * GET request decoded as JSON.
   */
  private function fetchJson(string $url, array $options): ?array {
    $result = \Drupal::httpClient()->get($url, $options);
    $data = json_decode((string) $result->getBody(), TRUE);
    return is_array($data) ? $data : NULL;
  }

  /**
   * Loads or creates the account of a Google or Facebook identity.
   *
   * An existing account is linked by the verified email only, never by its
   * name. The super admin, blocked accounts and accounts with an admin role
   * are refused, as on the Keycloak path.
   *
   * @param string $provider
   *   Provider name: google or facebook.
   * @param string $id
   *   Identifier of the person at the provider.
   * @param string $email
   *   Verified email, or an empty string.
   * @param string $realm
   *   WWW-Authenticate value of a refusal.
   * @param array $fields
   *   Field values of a new account.
   * @param string|null $picture_url
   *   Profile picture of a new account.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   */
  private function socialUser(string $provider, string $id, string $email, string $realm, array $fields, ?string $picture_url) {
    if ($id === '') {
      throw new UnauthorizedHttpException($realm, 'Access token could not be verified');
    }
    $identity = $provider . ':' . $id;
    $own_name = $id . '@' . $provider . '.com';
    $user = $email !== '' ? user_load_by_mail($email) : NULL;
    if (!$user) {
      $user = $this->socialIdentityUser($identity, $own_name);
    }
    if ($user) {
      $this->assertSocialAccountAllowed($user, $realm);
      return $user;
    }
    return $this->createSocialUser($provider, $id, $email, $fields, $picture_url);
  }

  /**
   * Refuses the super admin, blocked accounts and accounts with an admin role.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   */
  private function assertSocialAccountAllowed($user, string $realm): void {
    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    $admin = FALSE;
    foreach ($user->getRoles(TRUE) as $rid) {
      $role = $role_storage->load($rid);
      $admin = $admin || ($role && $role->isAdmin());
    }
    if ((int) $user->id() === 1 || $user->isBlocked() || $admin) {
      throw new UnauthorizedHttpException($realm, 'Access denied');
    }
  }

  /**
   * Creates the account of a Google or Facebook identity and marks it.
   */
  private function createSocialUser(string $provider, string $id, string $email, array $fields, ?string $picture_url) {
    $identity = $provider . ':' . $id;
    $own_name = $id . '@' . $provider . '.com';
    $name = $email;
    if ($name === '' || user_load_by_name($name)) {
      $name = user_load_by_name($own_name) ? $provider . '-' . hash('crc32b', $identity . microtime()) . '-' . $id : $own_name;
    }
    $values = [
      'status' => 1,
      'name' => $name,
      'mail' => $email !== '' ? $email : $own_name,
    ] + $fields;
    $file = $picture_url ? $this->socialPicture($picture_url, $id) : NULL;
    if ($file) {
      $values['user_picture'] = $file->id();
    }
    $user = User::create($values);
    $user->save();
    \Drupal::service('user.data')->set('vactory_decoupled', $user->id(), 'social_identity', $identity);
    return $user;
  }

  /**
   * Downloads the profile picture when user pictures are enabled.
   */
  private function socialPicture(string $picture_url, string $id) {
    $manager = $this->userManager;
    if ($manager && method_exists($manager, 'userPictureEnabled') && $manager->userPictureEnabled()) {
      return $manager->downloadProfilePic($picture_url, $id);
    }
    return NULL;
  }

  /**
   * Account created by this provider for an identity without verified email.
   *
   * Found by the identity stored in users_data, never by a name someone else
   * could register. An account created before that mark (same name and email,
   * no password) is taken over and marked.
   *
   * @param string $identity
   *   Provider name and identifier, e.g. "google:123".
   * @param string $own_name
   *   Name and email given to such accounts, e.g. "123@google.com".
   */
  private function socialIdentityUser(string $identity, string $own_name) {
    $uid = \Drupal::database()->select('users_data', 'd')
      ->fields('d', ['uid'])
      ->condition('module', 'vactory_decoupled')
      ->condition('name', 'social_identity')
      ->condition('value', $identity)
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($uid && ($user = User::load($uid))) {
      return $user;
    }
    $user_data = \Drupal::service('user.data');
    $user = user_load_by_name($own_name);
    if ($user && $user->getEmail() === $own_name && empty($user->getPassword())) {
      $user_data->set('vactory_decoupled', $user->id(), 'social_identity', $identity);
      return $user;
    }
    return NULL;
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

    $claims = $this->verifiedKeycloakClaims($jwt, $keycloak_issuer);
    $user = $this->keycloakUser($claims);

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
   * Verifies the token (signature, time claims, issuer, audience).
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   *   When the token cannot be verified.
   */
  private function verifiedKeycloakClaims(string $jwt, string $keycloak_issuer): array {
    $claims = $this->decodeKeycloakTokenWithRefresh($jwt, $keycloak_issuer);

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
    return $claims;
  }

  /**
   * Decodes the token, refreshing the JWKS once on an unknown "kid".
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   *   When the token cannot be verified.
   */
  private function decodeKeycloakTokenWithRefresh(string $jwt, string $keycloak_issuer): array {
    try {
      return $this->decodeKeycloakToken($jwt, $keycloak_issuer, FALSE);
    }
    catch (\UnexpectedValueException $exception) {
      // Unknown "kid": keys may have been rotated, refresh JWKS once.
      if (strpos($exception->getMessage(), '"kid"') === FALSE) {
        throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
      }
      try {
        return $this->decodeKeycloakToken($jwt, $keycloak_issuer, TRUE);
      }
      catch (\Exception $e) {
        throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
      }
    }
    catch (\Exception $exception) {
      throw new UnauthorizedHttpException('Bearer realm="keycloak"', 'Access token could not be verified');
    }
  }

  /**
   * Loads, links (verified email only) or creates the Drupal user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
   *   When the token has no username.
   */
  private function keycloakUser(array $claims) {
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
      $user = User::create([
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
      ]);
      $user->save();
    }
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
