<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\Core\Site\Settings;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the ssl certificate is valid or not.
 */
class BackendSslCertValidation extends Check {

  protected $targetedEnv;
  protected $validationMessage;
  protected $domain;
  protected $hasValidCert;
  const BACKEND_CONTEXT = 'backend';

  /**
   * {@inheritDoc}
   */
  public function __construct() {
    parent::__construct();
    $this->targetedEnv = static::BACKEND_CONTEXT;
    $this->validateSslCert();
  }

  /**
   * Validate SSL certificate.
   */
  protected function validateSslCert() {
    $this->domain = Settings::get('BASE_BACKOFFICE_URL');
    if (is_string($this->domain)) {
      $this->domain = preg_replace('#http[s]?://#', '', $this->domain);
    }
    if ($this->domain) {
      $validation_infos = \Drupal::service('vactory_security_review.helper')->hasValidSslCert($this->domain);
      $this->validationMessage = $validation_infos['message'];
      $this->hasValidCert = $validation_infos['success'];
    }
    if (!$this->domain) {
      $message = "BASE_BACKOFFICE_URL env variable is not defined! Please define it first then try again, example: BASE_BACKOFFICE_URL=https://backend.example.com";
      $this->validationMessage = $message;
      $this->hasValidCert = FALSE;
    }
    $this->validationMessage = "SSL validation: $this->validationMessage";
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
    return 'Backend SSL cert validation';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'back_ssl_cert_validation';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];
    if (!$this->domain || !$this->hasValidCert) {
      $result = CheckResult::FAIL;
    }
    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("Idéalement, HTTPS devrait être utilisé pour l'ensemble de votre application.");
    $paragraphs[] = $this->t("Si vous devez limiter son utilisation, HTTPS doit être appliqué à toutes les pages d'authentification ainsi qu'à toutes les pages une fois l'utilisateur authentifié.");
    $paragraphs[] = $this->t("Si des informations sensibles (par exemple, des informations personnelles) peuvent être soumises avant l'authentification, ces fonctionnalités doivent également être envoyées via HTTPS.");
    $paragraphs[] = $this->t("Toujours créer un lien vers la version HTTPS de l'URL si disponible.");
    $paragraphs[] = $this->t("S'appuyer sur la redirection de HTTP vers HTTPS augmente la possibilité pour un attaquant d'insérer une attaque man-in-the-middle sans éveiller les soupçons de l'utilisateur.");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t("SSL Certificate validation"),
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
