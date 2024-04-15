<?php

namespace Drupal\vactory_mailchimp_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides methods related to campaign creation and scheduling.
 *
 * @package Drupal\vactory_mailchimp_newsletter\Controller
 */
class CampaignsController extends ControllerBase {

  /**
   * Sends a Newsletter.
   */
  public function sendNewsletter($node, $recipients) {
    $url = Url::fromRoute('view.newsletter.listing');
    // Send created newsletter via mailchimp.
    \Drupal::service('vactory_mailchimp_newsletter.campaigns.manage')->mailchimpSendNewsletter($node, $recipients);
    return new RedirectResponse($url->toString());
  }

}
