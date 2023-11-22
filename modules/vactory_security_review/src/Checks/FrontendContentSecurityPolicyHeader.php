<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\Core\Site\Settings;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the Content-Security-Policy header is defined or not.
 */
class FrontendContentSecurityPolicyHeader extends Check {

  protected $validationMessage;
  protected $domain;
  protected $hasCspHeader;

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
      if (isset($headers['Content-Security-Policy']) || isset($headers['content-security-policy '])) {
        $this->hasCspHeader = TRUE;
        $this->validationMessage = "The Content-Security-Policy header is present in the response for $this->domain.";
      } else {
        $this->hasCspHeader = FALSE;
        $this->validationMessage = "The Content-Security-Policy header is NOT present in the response for $this->domain.";
      }
    }
    if (!$this->domain) {
      $message = "BASE_FRONTEND_URL env variable is not defined! Please define it first then try again, example: BASE_FRONTEND_URL=https://www.example.com";
      $this->validationMessage = $message;
      $this->hasCspHeader = FALSE;
    }
    $this->validationMessage = "Frontend Content-Security-Policy header: $this->validationMessage";
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
    return 'Frontend Content-Security-Policy header validation';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'front_hcsp_validation';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];

    if (!$this->domain || !$this->hasCspHeader) {
      $result = CheckResult::FAIL;
    }
    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];

    $paragraphs[] = $this->t("Utiliser l'en-tête X-Frame-Options header ou Content-Security-Policy (CSP) la directive frame-ancestors pour  empêcher le contenu d'être chargé par un site étranger dans un cadre.");
    $paragraphs[] = $this->t("Cela atténue les attaques de Clickjacking.");
    $paragraphs[] = $this->t("Pour les navigateurs plus anciens qui ne prennent pas en charge cet en-tête, ajouter du code Javascript framebusting pour atténuer Clickjacking (bien que cette méthode ne soit pas infaillible et puisse être contournée).");
    $paragraphs[] = $this->t("On Nextjs app Content-Security-Policy header is supposed to be defined in project.config.js file");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t("Content-Security-Policy header validation"),
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
