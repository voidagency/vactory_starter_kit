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
   * Initialise the JWT configuration.
   */
  private function initJwtConfiguration()
  {
    $this->jwtConfiguration = Configuration::forSymmetricSigner(
      new Sha256(),
      InMemory::plainText('0eylDkJplsBm22Meby8EKIeBMckMKMyO')
    );

    $keycloak_issuer = Settings::get('KEYCLOAK_ISSUER', "http://localhost:8003/auth/realms/master");

    $this->jwtConfiguration->setValidationConstraints(
      new IssuedBy($keycloak_issuer));
  }

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
      $this->initJwtConfiguration();
      $provider = $request->headers->get("X-Auth-Provider");
      $authorization = $request->headers->get('authorization');
      $jwt = trim(str_replace('Bearer ', '', $authorization));

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

      // user_load_by_mail
      $user = user_load_by_name($username);

      // create user
      // @todo:  // given_name
      // family_name
      // roles
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

    return parent::authenticate($request);
  }
}
