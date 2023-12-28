<?php

namespace Drupal\vactory_espace_prive\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Espace prive manager.
 */
class EspacePriveManager {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  protected $configFactory;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Reset webmasters password.
   */
  public function resetWebmastersPasswords() {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'roles' => 'webmaster'
      ]);
    $config = $this->configFactory->get('vactory_espace_prive.settings');
    $password_lifetime = $config->get('password_lifetime');
    if ($password_lifetime) {
      foreach ($users as $user) {
        $now = time();
        if ($user->hasField('field_reset_password_date')) {
          // Last reset password date.
          $lrp_date = $user->get('field_reset_password_date')->value;
          if (empty($lrp_date) || !is_numeric($lrp_date)) {
            $lrp_date = $now;
          }
          $diff_days = ($now - (int) $lrp_date)/(60 * 60 * 24);
          if ($diff_days >= (int) $password_lifetime) {
            $user->setPassword('reSet'. $now);
            $lrp_date = $now;
          }
          $user->set('field_reset_password_date', $lrp_date);
          $user->save();
        }
      }
    }
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
