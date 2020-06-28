<?php

namespace Drupal\vactory_tender\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Provide a form to collect necessary user info.
 *
 * Before downloading requested tender document.
 *
 * @package Drupal\vactory_render\Controller
 */
class TenderFormPage extends ControllerBase {

  /**
   * Provide the subscription form page content.
   */
  public function content($crypted_nid, Request $request) {
    $nid = vactory_tender_decrypt($crypted_nid);
    if (is_numeric($nid)) {
      $node = Node::load($nid);
      if ($node instanceof NodeInterface && $node->bundle() == 'vactory_tender') {
        $title = $node->getTitle();
        $reference = $node->get('field_vactory_reference')->value;

        // Get tender webform.
        $webform = \Drupal::entityTypeManager()
          ->getStorage('webform')
          ->load('vactory_tender_form');

        // Add crypted nid as hidden field so we can access it from form_state.
        $webform->setElementProperties('crypted_nid',[
          '#type' => 'hidden',
          '#value' => $crypted_nid,
        ]);
        $webform = $webform->getSubmissionForm();

        return [
          '#theme'   => 'tender_subscription_page',
          '#content' => [
            'title'     => $title,
            'reference' => $reference,
            'form'      => $webform,
          ],
        ];
      }
      else {
        return new RedirectResponse(Url::fromRoute('system.404')
          ->toString());
      }
    }
    else {
      return new RedirectResponse(Url::fromRoute('system.404')
        ->toString());
    }
  }

}
