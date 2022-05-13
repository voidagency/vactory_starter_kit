<?php

namespace Drupal\vactory_image_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Laminas\Diactoros\Response\XmlResponse;
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
      return new XmlResponse($xml);
    }
    throw new NotFoundHttpException();
  }

}
