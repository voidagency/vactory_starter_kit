<?php
//
//namespace Drupal\vactory_core\Controller;
//
//use Drupal\Core\Url;
//use Drupal\node\Controller\NodeController;
//
///**
// * Provides a list of block plugins to be added to the layout.
// */
//class VactoryNodeController extends NodeController {
//
//  public function addVactoryPage() {
//    $build = parent::addPage();
//    if (is_array($build) && isset($build['#content']['vactory_generic_type']) && !empty($build['#content']['vactory_generic_type'])) {
//      unset($build['#content']['vactory_generic_type']);
//    }
//    return $build;
//  }
//}
