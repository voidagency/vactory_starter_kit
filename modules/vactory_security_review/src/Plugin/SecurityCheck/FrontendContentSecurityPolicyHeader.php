<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether the Content-Security-Policy header is defined or not.
 */
#[SecurityCheck(
  id: 'front_hcsp_validation',
  title: new TranslatableMarkup('Frontend Content-Security-Policy header validation'),
  description: new TranslatableMarkup('Checks whether the frontend Content-Security-Policy header is defined or not.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup('Frontend Content-Security-Policy header is present.'),
  failure_message: new TranslatableMarkup('Frontend Content-Security-Policy header is NOT present.'),
  help: [
    new TranslatableMarkup("Utiliser l'en-tête X-Frame-Options header ou Content-Security-Policy (CSP) la directive frame-ancestors pour  empêcher le contenu d'être chargé par un site étranger dans un cadre."),
    new TranslatableMarkup('Cela atténue les attaques de Clickjacking.'),
    new TranslatableMarkup("Pour les navigateurs plus anciens qui ne prennent pas en charge cet en-tête, ajouter du code Javascript framebusting pour atténuer Clickjacking (bien que cette méthode ne soit pas infaillible et puisse être contournée)."),
    new TranslatableMarkup('On Nextjs app Content-Security-Policy header is supposed to be defined in project.config.js file'),
  ]
)]
class FrontendContentSecurityPolicyHeader extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $this->checkHeader('BASE_FRONTEND_URL', 'https://www.example.com', 'Frontend', 'Content-Security-Policy');
  }

}
