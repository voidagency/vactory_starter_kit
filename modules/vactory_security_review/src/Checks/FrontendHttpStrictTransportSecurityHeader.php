<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\Core\Site\Settings;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the HTTP Strict Transport Security header is defined or not.
 */
class FrontendHttpStrictTransportSecurityHeader extends Check {

  protected $validationMessage;
  protected $domain;
  protected $hasHstsHeader;

  /**
   * {@inheritDoc}
   */
  public function __construct() {
    parent::__construct();
    $this->checkHstsHeaderExistence();
  }

  /**
   * Check whether the HSTS header is defined or not.
   */
  protected function checkHstsHeaderExistence() {
    $this->domain = Settings::get('BASE_FRONTEND_URL');
    if (is_string($this->domain)) {
      $this->domain = preg_replace('#http[s]?://#', '', $this->domain);
    }
    if ($this->domain) {
      $url = "https://$this->domain";
      $validation_infos = \Drupal::service('vactory_security_review.helper')->hasValidSslCert($this->domain);
      $is_valid_ssl = $validation_infos['success'];
      if ($is_valid_ssl) {
        // Perform the request and check for the Strict-Transport-Security header.
        $headers = get_headers($url, 1);
      }
      if (isset($headers['Strict-Transport-Security']) || isset($headers['strict-transport-security'])) {
        $this->hasHstsHeader = TRUE;
        $this->validationMessage = "The Strict-Transport-Security header is present in the response for $this->domain.";
      } else {
        $this->hasHstsHeader = FALSE;
        $this->validationMessage = "The Strict-Transport-Security header is NOT present in the response for $this->domain.";
      }
    }
    if (!$this->domain) {
      $message = "BASE_FRONTEND_URL env variable is not defined! Please define it first then try again, example: BASE_FRONTEND_URL=https://www.example.com";
      $this->validationMessage = $message;
      $this->hasHstsHeader = FALSE;
    }
    $this->validationMessage = "Frontend Strict-Transport-Security header: $this->validationMessage";
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Frontend Strict-Transport-Security header validation';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'front_hsts_validation';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];

    if (!$this->domain || !$this->hasHstsHeader) {
      $result = CheckResult::FAIL;
    }
    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];

    $paragraphs[] = $this->t("L'en-tête Strict-Transport-Security garantit que le navigateur ne communique pas avec le serveur via HTTP.");
    $paragraphs[] = $this->t("Cela permet de réduire le risque d'attaques de rétrogradation HTTP telles qu'implémentées par l'outil sslsniff.");
    $paragraphs[] = $this->t("Pistes:");
    $paragraphs[] = $this->t("On Nextjs app Strict-Transport-Security header is supposed to be defined in project.config.js file");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t("Strict-Transport-Security header validation"),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    return $this->validationMessage;
  }

}
