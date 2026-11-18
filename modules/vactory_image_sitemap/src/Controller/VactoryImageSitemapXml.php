<?php

namespace Drupal\vactory_image_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Vactory Image Sitemap routes.
 */
class VactoryImageSitemapXml extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $file = 'public://image_sitemap/' . $langcode . '_image_sitemap.xml';
    if (file_exists($file)) {
      $xml = file_get_contents($file);
      return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
    throw new NotFoundHttpException();
  }

}
