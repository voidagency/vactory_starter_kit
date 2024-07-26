<?php

namespace Drupal\vactory_decoupled;

use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider as BaseSimpleOauthAuthenticationProvider;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Drupal\Core\Site\Settings;
use Lcobucci\JWT\Exception;

/**
 * Simple oauth authentication provider class.
 */
class SimpleOauthAuthenticationProvider extends BaseSimpleOauthAuthenticationProvider {

  /**
   * Jwt configuration.
   *
   * @var array
   */
  private $jwtConfiguration;

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
   * @throws \League\OAuth2\Server\Exception\OAuthServerException
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
    $last_name = $payload["family_name"];
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
   */
  private function authenticateKeycloak(string $jwt) {
    $this->jwtConfiguration = Configuration::forSymmetricSigner(
      new Sha256(),
      InMemory::plainText('0eylDkJplsBm22Meby8EKIeBMckMKMyO')
    );

    $keycloak_issuer = Settings::get('KEYCLOAK_ISSUER', "https://keycloak.lecontenaire.com/auth/realms/dev");

    $this->jwtConfiguration->setValidationConstraints(
      new IssuedBy($keycloak_issuer));

    try {
      // Attempt to parse the JWT.
      $token = $this->jwtConfiguration->parser()->parse($jwt);
    }
    catch (Exception $exception) {
      throw OAuthServerException::accessDenied($exception->getMessage(), NULL, $exception);
    }

    try {
      // Attempt to validate the JWT.
      $constraints = $this->jwtConfiguration->validationConstraints();
      $this->jwtConfiguration->validator()->assert($token, ...$constraints);
    }
    catch (RequiredConstraintsViolated $exception) {
      throw OAuthServerException::accessDenied('Access token could not be verified');
    }

    $claims = $token->claims();
    $username = $claims->get("preferred_username");
    $mail = $claims->get("email", $username . "@keycloak.com");

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
      ];

      $user = User::create($values);
      $user->save();
    }
    $this->moduleHandler->alter('simple_oauth_authentication_keycloak', $user, $claims, $jwt);
    return $user;
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
