<?php

namespace Drupal\vactory_tender\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Provide a form to collect necessary user info.
 *
 * Before downloading requested tender document.
 *
 * @package Drupal\vactory_render\Controller
 */
class TenderFileDownloader extends ControllerBase {

  /**
   * Provide the download file page content.
   */
  public function content($crypted_nid, Request $request) {
    $nid = vactory_tender_decrypt($crypted_nid);
    if (is_numeric($nid)) {
      $node = Node::load($nid);
      if ($node instanceof NodeInterface && $node->bundle() == 'vactory_tender') {
        $title = $node->getTitle();
        $reference = $node->get('field_vactory_reference')->value;
        $fid = $node->get('field_vactory_media_file')->getValue()[0]['target_id'];
        $file_uri = File::load($fid)->getFileUri();

        return [
          '#theme'   => 'tender_download_page',
          '#content' => [
            'title'     => $title,
            'reference' => $reference,
            'file_uri'  => $file_uri,
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
