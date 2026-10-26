<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether the frontend Strict-Transport-Security header is defined.
 */
#[SecurityCheck(
  id: 'front_hsts_validation',
  title: new TranslatableMarkup('Frontend Strict-Transport-Security header validation'),
  description: new TranslatableMarkup('Checks whether the frontend Strict-Transport-Security header is defined or not.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup('Frontend Strict-Transport-Security header is present.'),
  failure_message: new TranslatableMarkup('Frontend Strict-Transport-Security header is NOT present.'),
  help: [
    new TranslatableMarkup("L'en-tête Strict-Transport-Security garantit que le navigateur ne communique pas avec le serveur via HTTP."),
    new TranslatableMarkup("Cela permet de réduire le risque d'attaques de rétrogradation HTTP telles qu'implémentées par l'outil sslsniff."),
    new TranslatableMarkup('Pistes:'),
    new TranslatableMarkup('On Nextjs app Strict-Transport-Security header is supposed to be defined in project.config.js file'),
  ]
)]
class FrontendHttpStrictTransportSecurityHeader extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $this->checkHeader('BASE_FRONTEND_URL', 'https://www.example.com', 'Frontend', 'Strict-Transport-Security');
  }

}
