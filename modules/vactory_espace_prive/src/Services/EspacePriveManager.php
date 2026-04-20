<?php

namespace Drupal\vactory_espace_prive\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\UserInterface;

/**
 * Espace prive manager.
 */
class EspacePriveManager {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs an EspacePriveManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    TimeInterface $time,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->time = $time;
  }

  /**
   * Reset webmasters password.
   */
  public function resetWebmastersPasswords() {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'roles' => 'webmaster',
      ]);
    $config = $this->configFactory->get('vactory_espace_prive.settings');
    $password_lifetime = $config->get('password_lifetime');
    if ($password_lifetime) {
      $now = $this->time->getRequestTime();
      foreach ($users as $user) {
        if ($user->hasField('field_reset_password_date')) {
          $lrp_timestamp = $this->getLastResetPasswordTimestamp($user);
          if ($lrp_timestamp === NULL) {
            $user->set('field_reset_password_date', $this->formatDatetimeStorageValue($now));
            $user->save();
            continue;
          }
          $diff_days = ($now - $lrp_timestamp) / (60 * 60 * 24);
          if ($diff_days >= (int) $password_lifetime) {
            $user->setPassword('reSet' . $now);
            $user->set('field_reset_password_date', $this->formatDatetimeStorageValue($now));
            $user->save();
          }
        }
      }
    }
  }

  /**
   * Unix timestamp from field_reset_password_date (datetime field, ISO UTC).
   */
  protected function getLastResetPasswordTimestamp(UserInterface $user): ?int {
    $raw = $user->get('field_reset_password_date')->value;
    if ($raw === NULL || $raw === '') {
      return NULL;
    }
    // Allow legacy values stored as a numeric string.
    if (ctype_digit((string) $raw)) {
      return (int) $raw;
    }
    $date = \DateTimeImmutable::createFromFormat(
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      $raw,
      new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE)
    );
    if ($date instanceof \DateTimeImmutable) {
      return $date->getTimestamp();
    }
    return NULL;
  }

  /**
   * Formats a unix timestamp for datetime field storage (UTC).
   *
   * @param int $timestamp
   *   Unix timestamp.
   *
   * @return string
   *   Datetime storage string.
   */
  protected function formatDatetimeStorageValue(int $timestamp): string {
    return gmdate(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timestamp);
  }

  /**
   * Validate black listed mail.
   */
  public function validateEmail($email) {
    $valid = TRUE;
    $config = $this->configFactory->get('vactory_espace_prive.settings');
    if (!empty($config->get('domain_black_list'))) {
      $valid_domains = explode(';', $config->get('domain_black_list'));
      $email_domain = explode('@', $email)[1];
      if (in_array($email_domain, $valid_domains)) {
        $valid = FALSE;
      }
    }
    return $valid;
  }

}
