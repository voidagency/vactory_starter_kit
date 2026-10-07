<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;

/**
 * Resolves overrides or decrypts a key stored outside exported configuration.
 */
final class ApiKeyStore {

  public const STATE_KEY = 'vactory_webform_typesafe.api_key';

  /**
   * Constructs the credential store.
   */
  public function __construct(private readonly StateInterface $state) {}

  /**
   * Reports the active source without exposing the key.
   */
  public function source(): string {
    if (Settings::get('vactory_webform_typesafe_api_key', '') !== '') {
      return 'settings.php';
    }
    $environment = getenv('TYPESAFE_API_KEY');
    if ($environment !== FALSE && trim($environment) !== '') {
      return 'environment';
    }
    return $this->state->get(self::STATE_KEY) ? 'stored' : 'none';
  }

  /**
   * Resolves the effective key, with server overrides taking precedence.
   */
  public function get(): string {
    $source = $this->source();
    if ($source === 'settings.php') {
      return self::normalize(Settings::get('vactory_webform_typesafe_api_key'));
    }
    if ($source === 'environment') {
      return self::normalize(getenv('TYPESAFE_API_KEY'));
    }
    if ($source === 'none') {
      throw new JevApiException('Configure a Jev API key in Webform TypeSafe settings.');
    }
    $stored = $this->state->get(self::STATE_KEY);
    $bytes = is_string($stored) && str_starts_with($stored, 'v1:') ? base64_decode(substr($stored, 3), TRUE) : FALSE;
    if ($bytes === FALSE || strlen($bytes) < 29) {
      throw new JevApiException('The stored API key cannot be read. Enter it again in Webform TypeSafe settings.');
    }
    $plain = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, substr($bytes, 0, 12), substr($bytes, 12, 16), self::STATE_KEY);
    if ($plain === FALSE) {
      throw new JevApiException('The stored API key cannot be decrypted. Enter it again after a site salt change.');
    }
    return self::normalize($plain);
  }

  /**
   * Encrypts a replacement key using the site's non-exported hash salt.
   */
  public function save(#[\SensitiveParameter] string $key): void {
    $key = self::normalize($key);
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($key, 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, $nonce, $tag, self::STATE_KEY);
    if ($ciphertext === FALSE) {
      throw new JevApiException('Could not securely store the API key.');
    }
    $this->state->set(self::STATE_KEY, 'v1:' . base64_encode($nonce . $tag . $ciphertext));
  }

  /**
   * Deletes only the locally stored key, leaving server overrides untouched.
   */
  public function delete(): void {
    $this->state->delete(self::STATE_KEY);
  }

  /**
   * Validates a key before it can become an HTTP header.
   */
  public static function normalize(#[\SensitiveParameter] mixed $key): string {
    if (!is_string($key) || trim($key) === '') {
      throw new JevApiException('Enter a nonempty Jev API key.');
    }
    $key = trim($key);
    if (strlen($key) > 4096 || preg_match('/[\x00-\x20\x7F]/', $key)) {
      throw new JevApiException('The API key contains invalid whitespace or control characters.');
    }
    return $key;
  }

  /**
   * Derives a purpose-specific encryption key from settings.php's hash salt.
   */
  private function encryptionKey(): string {
    $salt = Settings::get('hash_salt', '');
    if (!is_string($salt) || $salt === '') {
      throw new JevApiException('A Drupal hash salt is required to store an API key.');
    }
    return hash_hkdf('sha256', $salt, 32, self::STATE_KEY);
  }

}
