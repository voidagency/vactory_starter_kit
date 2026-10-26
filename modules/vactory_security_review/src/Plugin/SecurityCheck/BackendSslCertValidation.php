<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether the backend ssl certificate is valid or not.
 */
#[SecurityCheck(
  id: 'back_ssl_cert_validation',
  title: new TranslatableMarkup('Backend SSL cert validation'),
  description: new TranslatableMarkup('Checks whether the backend SSL certificate is valid or not.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup('SSL validation: SSL certificate is valid.'),
  failure_message: new TranslatableMarkup('SSL validation: SSL certificate is invalid.'),
  help: [
    new TranslatableMarkup("Idéalement, HTTPS devrait être utilisé pour l'ensemble de votre application."),
    new TranslatableMarkup("Si vous devez limiter son utilisation, HTTPS doit être appliqué à toutes les pages d'authentification ainsi qu'à toutes les pages une fois l'utilisateur authentifié."),
    new TranslatableMarkup("Si des informations sensibles (par exemple, des informations personnelles) peuvent être soumises avant l'authentification, ces fonctionnalités doivent également être envoyées via HTTPS."),
    new TranslatableMarkup("Toujours créer un lien vers la version HTTPS de l'URL si disponible."),
    new TranslatableMarkup("S'appuyer sur la redirection de HTTP vers HTTPS augmente la possibilité pour un attaquant d'insérer une attaque man-in-the-middle sans éveiller les soupçons de l'utilisateur."),
  ]
)]
class BackendSslCertValidation extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $this->checkSslCert('BASE_BACKOFFICE_URL', 'https://backend.example.com');
  }

}
