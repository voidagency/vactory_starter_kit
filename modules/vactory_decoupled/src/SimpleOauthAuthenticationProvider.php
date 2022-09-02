<?php

namespace Drupal\vactory_decoupled;

use DateTimeZone;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider as BaseSimpleOauthAuthenticationProvider;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Drupal\Core\Site\Settings;

/**
 * @internal
 */
class SimpleOauthAuthenticationProvider extends BaseSimpleOauthAuthenticationProvider
{
  /**
   * @var Configuration
   */
  private $jwtConfiguration;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \League\OAuth2\Server\Exception\OAuthServerException
   */
  public function authenticate(Request $request)
  {
    if ($request->headers->has("X-Auth-Provider")) {
      $provider = $request->headers->get("X-Auth-Provider");
      $authorization = $request->headers->get('authorization');
      $jwt = trim(str_replace('Bearer ', '', $authorization));

      if ($provider === "keycloak") {
        return $this->authenticateKeycloak($jwt);
      }
      else if ($provider === "facebook") {
        return $this->authenticateFacebook($jwt);
      }
      else if ($provider === "google") {
        return $this->authenticateGoogle($jwt);
      }
    }

    return parent::authenticate($request);
  }

  private function authenticateGoogle($jwt) {
    // $result = \Drupal::httpClient()->get("https://graph.facebook.com/me?access_token=${jwt}&fields=id,name,email,picture");
    $result = \Drupal::httpClient()->get("https://openidconnect.googleapis.com/v1/userinfo", [
      'headers' => [
        'Authorization' => "Bearer " .$jwt,
      ],
    ]);
    $payload = json_decode((string) $result->getBody(), true);

    $id = $payload["sub"];
    $name = $payload["name"];
    $first_name = $payload["given_name"];
    $last_name = $payload["family_name"];
    $email = $payload["email"];
    $picture = $payload["picture"];
    $username = (!empty($email)) ? $email : "${id}@google.com";
    $mail = (!empty($email)) ? $email : "${id}@google.com";
    $values = [
      'status' => 1,
      'name' => $username,
      'mail' => $mail,
      'field_first_name' => $first_name,
      'field_last_name' => $last_name,
    ];

    $user = user_load_by_name($username);
    if (!$user) {
      $values = [
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
        'field_first_name' => $first_name,
        'field_last_name' => $last_name,
      ];

      $user = User::create($values);
      $user->save();
    }
    return $user;
  }

  private function authenticateFacebook($jwt) {
    // $result = \Drupal::httpClient()->get("https://graph.facebook.com/me?access_token=${jwt}&fields=id,name,email,picture");
    $result = \Drupal::httpClient()->get("https://graph.facebook.com/me", [
      'query' => [
        'access_token' => $jwt,
        'fields' => 'id,name,email,picture'
      ],
    ]);
    $payload = json_decode((string) $result->getBody(), true);

    $id = $payload["id"];
    $name = $payload["name"];
    list($first_name, $last_name) = array_pad( explode( ' ', $name ), 4, '' );
    $email = $payload["email"];
    $picture = $payload["picture"];
    $username = (!empty($email)) ? $email : "${id}@facebook.com";
    $mail = (!empty($email)) ? $email : "${id}@facebook.com";
    $values = [
      'status' => 1,
      'name' => $username,
      'mail' => $mail,
      'field_first_name' => $first_name,
      'field_last_name' => $last_name,
    ];

    $user = user_load_by_name($username);
    if (!$user) {
      $values = [
        'status' => 1,
        'name' => $username,
        'mail' => $mail,
        'field_first_name' => $first_name,
        'field_last_name' => $last_name,
      ];

      $user = User::create($values);
      $user->save();
    }
    return $user;
  }

  private function authenticateKeycloak($jwt) {
    $this->jwtConfiguration = Configuration::forSymmetricSigner(
      new Sha256(),
      InMemory::plainText('0eylDkJplsBm22Meby8EKIeBMckMKMyO')
    );

    // $keycloak_issuer = Settings::get('KEYCLOAK_ISSUER', "http://localhost:8003/auth/realms/master");
    $keycloak_issuer = Settings::get('KEYCLOAK_ISSUER', "https://keycloak.lecontenaire.com/auth/realms/dev");

    $this->jwtConfiguration->setValidationConstraints(
      new IssuedBy($keycloak_issuer));

      try {
        // Attempt to parse the JWT
        $token = $this->jwtConfiguration->parser()->parse($jwt);
      } catch (\Lcobucci\JWT\Exception $exception) {
        throw OAuthServerException::accessDenied($exception->getMessage(), null, $exception);
      }

      try {
        // Attempt to validate the JWT
        $constraints = $this->jwtConfiguration->validationConstraints();
        $this->jwtConfiguration->validator()->assert($token, ...$constraints);
      } catch (RequiredConstraintsViolated $exception) {
        throw OAuthServerException::accessDenied('Access token could not be verified');
      }

      $claims = $token->claims();
      $username = $claims->get("preferred_username");
      $mail = $claims->get("email", $username . "@keycloak.com");  

      $user = user_load_by_name($username);

      if (!$user) {
        $values = [
          'status' => 1,
          'name' => $username,
          'mail' => $mail,
        ];

        $user = User::create($values);
        $user->save();
      }

      return $user;
  }
}
