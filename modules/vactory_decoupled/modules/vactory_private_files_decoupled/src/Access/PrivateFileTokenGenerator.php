<?php

namespace Drupal\vactory_private_files_decoupled\Access;

use DateTime;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;

/**
 * Generates and validates private file tokens.
 */
class PrivateFileTokenGenerator {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Time, for how long is the private file token valid (in seconds).
   *
   * @var int
   */
  protected $expirationTime;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs the private file token generator.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The private file token config.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(PrivateKey $private_key, TimeInterface $time) {
    $this->privateKey = $private_key;
    $now = new DateTime();
    $this->expirationTime = $now->getTimestamp() + 300;
    $this->time = $time;
  }

  /**
   * Generates a token based on file uri and timestamp.
   *
   * @param string $uri
   *   Private file/image uri which by default starts with "/system/files/*". In
   *   case of images, uri already contains image style path part. For example
   *   "/system/files/styles/thumbnail/private/test.png".
   * @param int $timestamp
   *   Unix timestamp.
   *
   * @return string
   *   A 43-character URL-safe token for validation, based on the hash salt
   *   provided by Settings::getHashSalt(), and the 'drupal_private_key'
   *   configuration variable.
   */
  public function get(string $uri, int $timestamp): string {
    return Crypt::hmacBase64($uri . $timestamp, $this->privateKey->get() . Settings::getHashSalt());
  }

  /**
   * Validates a private file token.
   *
   * @param string $token
   *   The token to be validated.
   * @param string $uri
   *   Uri used for validation.
   * @param int $timestamp
   *   Unix timestamp used for validation.
   *
   * @return bool
   *   TRUE for a valid token, FALSE for an invalid token.
   */
  public function validate(string $token, string $uri, int $timestamp): bool {
    // Make sure token did not expire.
    if ($this->time->getRequestTime() - $timestamp > $this->expirationTime) {
      return FALSE;
    }
    $expected_token = $this->get($uri, $timestamp);
    return hash_equals($expected_token, $token);
  }

}
