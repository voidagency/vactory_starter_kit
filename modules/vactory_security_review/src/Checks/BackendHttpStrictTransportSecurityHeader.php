<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\Core\Site\Settings;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the HTTP Strict Transport Security header is defined or not.
 */
class BackendHttpStrictTransportSecurityHeader extends Check {

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
    $this->domain = Settings::get('BASE_BACKOFFICE_URL');
    if (is_string($this->domain)) {
      $this->domain = preg_replace('#http[s]?://#', '', $this->domain);
      $this->domain = trim($this->domain, '/user');
    }
    if ($this->domain) {
      $url = "https://$this->domain/user";
      $validation_infos = \Drupal::service('vactory_security_review.helper')->hasValidSslCert($this->domain);
      $is_valid_ssl = $validation_infos['success'];
      if ($is_valid_ssl) {
        // Perform the request and check for the Strict-Transport-Security header.
        $headers = get_headers($url, 1);
      }
      if (isset($headers['Strict-Transport-Security'])) {
        $this->hasHstsHeader = TRUE;
        $this->validationMessage = "The Strict-Transport-Security header is present in the response for $this->domain.";
      } else {
        $this->hasHstsHeader = FALSE;
        $this->validationMessage = "The Strict-Transport-Security header is NOT present in the response for $this->domain. If htaccess is enabled for this domain please disable it and try again!";
      }
    }
    if (!$this->domain) {
      $message = "BASE_BACKOFFICE_URL env variable is not defined! Please define it first then try again, example: BASE_BACKOFFICE_URL=https://backend.example.com";
      $this->validationMessage = $message;
      $this->hasHstsHeader = FALSE;
    }
    $this->validationMessage = "Backend Strict-Transport-Security header: $this->validationMessage";
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
    return 'Backend Strict-Transport-Security header validation';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'back_hsts_validation';
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
    $paragraphs[] = $this->t("On Drupal project, Strict-Transport-Security header could be enabled using Security Kit (seckit) contrib module setting form (/admin/config/system/seckit) in SSL/TLS group field ensure that \"HTTP Strict Transport Security\" and \"Include Subdomains\" checkboxes are checked then save configuration.");

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
