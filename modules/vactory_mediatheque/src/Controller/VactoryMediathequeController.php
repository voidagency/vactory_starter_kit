<?php

namespace Drupal\vactory_mediatheque\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Mediatheque Controller.
 */
class VactoryMediathequeController extends ControllerBase {

  /**
   * Generate terms function.
   */
  public function generateTerms(Request $request) {
    $term_data = $request->getContent();
    if (!empty($term_data)) {
      $params = json_decode($term_data, TRUE);
    }
    if (empty($params['value'])) {
      return new JsonResponse([
        'error' => [
          'code'    => '400',
          'message' => 'Missing Term infos.',
        ],
      ], 400);
    }
    $vid = 'mediatheque_theme_albums';
    // The parent term id.
    $parent_tid = $params['value'];
    // 1 to get only immediate children, NULL to load entire tree.
    $depth = 1;
    // True will return loaded entities rather than ids.
    $load_entities = FALSE;
    $children_ids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $parent_tid, $depth, $load_entities);
    return new JsonResponse([
      'sucess' => 'The term was loaded.',
      'children' => $children_ids,
    ], 200);
  }

}
