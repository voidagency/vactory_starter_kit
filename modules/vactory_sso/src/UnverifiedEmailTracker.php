<?php

namespace Drupal\vactory_sso;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Psr\Log\LoggerInterface;

/**
 * Records the SSO logins refused for an unverified email.
 *
 * Since 3.1.1, oauth_login_oauth2 refuses the login when the identity
 * provider does not report email_verified = true, and logs it only when its
 * debug logging is on. This tracker applies the same rule to the userinfo
 * claims captured by vactory_sso, logs each refusal and keeps one entry per
 * address, for the status report and "drush vactory-sso:unverified-emails".
 */
final class UnverifiedEmailTracker {

  /**
   * Key-value collection: one entry per refused address.
   */
  public const COLLECTION = 'vactory_sso.unverified_email';

  /**
   * Maximum number of addresses kept.
   */
  public const MAX_ENTRIES = 500;

  /**
   * Constructs an UnverifiedEmailTracker object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key-value factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The vactory_sso logger channel.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly KeyValueFactoryInterface $keyValueFactory,
    private readonly LoggerInterface $logger,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Whether oauth_login_oauth2 3.1.1 refuses these claims.
   *
   * Same rule as the module: a claim present must be true; an absent claim
   * is accepted only when the module trusts unverified emails.
   *
   * @param array $claims
   *   Userinfo claims.
   * @param bool $trust_unverified
   *   The miniorange_oauth_client_trust_unverified_email setting.
   */
  public static function isRefused(array $claims, bool $trust_unverified): bool {
    if (!array_key_exists('email_verified', $claims)) {
      return !$trust_unverified;
    }
    return filter_var($claims['email_verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== TRUE;
  }

  /**
   * Records the claims of an SSO callback when the login will be refused.
   *
   * @param array $claims
   *   Userinfo claims.
   */
  public function record(array $claims): void {
    $config = $this->configFactory->get('oauth_login_oauth2.settings');
    if (!self::isRefused($claims, (bool) $config->get('miniorange_oauth_client_trust_unverified_email'))) {
      return;
    }
    $attribute = $config->get('miniorange_oauth_client_email_attr_val') === 'other'
      ? $config->get('miniorange_oauth_client_other_field_for_email')
      : $config->get('miniorange_oauth_client_email_attr_val');
    $email = $attribute && is_scalar($claims[$attribute] ?? NULL) ? mb_strtolower(trim((string) $claims[$attribute])) : '';
    $claim = array_key_exists('email_verified', $claims) ? var_export($claims['email_verified'], TRUE) : 'absent';

    $this->logger->warning('SSO login refused by oauth_login_oauth2: the identity provider reports email_verified = @claim for %email.', [
      '@claim' => $claim,
      '%email' => $email === '' ? '(no email)' : $email,
    ]);

    $key = $email === '' ? '(no email)' : $email;
    $store = $this->store();
    $now = $this->time->getRequestTime();
    $entry = $store->get($key);
    if ($entry === NULL && count($store->getAll()) >= self::MAX_ENTRIES) {
      return;
    }
    $entry ??= ['count' => 0, 'first' => $now];
    $entry['count']++;
    $entry['last'] = $now;
    $entry['claim'] = $claim;
    $store->set($key, $entry);
  }

  /**
   * Returns the refused addresses, most recent first.
   *
   * @return array
   *   Entries (count, first, last, claim) keyed by address.
   */
  public function all(): array {
    $entries = $this->store()->getAll();
    uasort($entries, static fn(array $a, array $b): int => $b['last'] <=> $a['last']);
    return $entries;
  }

  /**
   * Forgets every recorded address.
   */
  public function reset(): void {
    $this->store()->deleteAll();
  }

  /**
   * The key-value store of the tracker.
   */
  private function store(): KeyValueStoreInterface {
    return $this->keyValueFactory->get(self::COLLECTION);
  }

}
