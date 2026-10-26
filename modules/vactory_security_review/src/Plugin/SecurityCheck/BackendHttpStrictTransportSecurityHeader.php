<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether the backend Strict-Transport-Security header is defined.
 */
#[SecurityCheck(
  id: 'back_hsts_validation',
  title: new TranslatableMarkup('Backend Strict-Transport-Security header validation'),
  description: new TranslatableMarkup('Checks whether the backend Strict-Transport-Security header is defined or not.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup('Backend Strict-Transport-Security header is present.'),
  failure_message: new TranslatableMarkup('Backend Strict-Transport-Security header is NOT present.'),
  help: [
    new TranslatableMarkup("L'en-tête Strict-Transport-Security garantit que le navigateur ne communique pas avec le serveur via HTTP."),
    new TranslatableMarkup("Cela permet de réduire le risque d'attaques de rétrogradation HTTP telles qu'implémentées par l'outil sslsniff."),
    new TranslatableMarkup('Pistes:'),
    new TranslatableMarkup('On Drupal project, Strict-Transport-Security header could be enabled using Security Kit (seckit) contrib module setting form (/admin/config/system/seckit) in SSL/TLS group field ensure that "HTTP Strict Transport Security" and "Include Subdomains" checkboxes are checked then save configuration.'),
  ]
)]
class BackendHttpStrictTransportSecurityHeader extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $this->checkHeader('BASE_BACKOFFICE_URL', 'https://backend.example.com', 'Backend', 'Strict-Transport-Security', '/user', 'If htaccess is enabled for this domain please disable it and try again!');
  }

}
