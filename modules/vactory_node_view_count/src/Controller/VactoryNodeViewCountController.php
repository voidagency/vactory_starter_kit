<?php

namespace Drupal\vactory_node_view_count\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Vactory node view count routes.
 */
class VactoryNodeViewCountController extends ControllerBase {

  /**
   * Update counter (get nid from path).
   */
  public function incrementCounter($nid) {
    $node = Node::load($nid);
    $bundle = $node->bundle();
    $type = NodeType::load($bundle);
    $view_count_enabled = (bool) $type->getThirdPartySetting('vactory_node_view_count', 'enabling_count_node', '');
    if (!$view_count_enabled) {
      return new JsonResponse([
        'message' => $this->t("Views count is not enabled for {$bundle}"),
        'code' => -3,
      ], 400);
    }
    if ($node instanceof NodeInterface) {
      if ($node->hasField('field_node_count_view')) {
        $count = isset($node->get('field_node_count_view')->getValue()[0]) ? $node->get('field_node_count_view')->getValue()[0]['value'] : 0;
        $count++;
        $node->set('field_node_count_view', $count);
        $node->save();
        return new JsonResponse([
          'message' => $this->t('Node count has been incremented'),
          'count' => $node->get('field_node_count_view')->getValue()[0]['value'],
          'code' => 1,
        ]);
      }
      return new JsonResponse([
        'message' => $this->t('The specific node has no field (count views)!'),
        'code' => -1,
      ], 400);
    }
    return new JsonResponse([
      'message' => $this->t('Invalid node id!'),
      'code' => -2,
    ], 400);
  }

  /**
   * Update counter (get nid from request body).
   */
  public function updateCounter(Request $request) {
    $nid = $request->request->all('nid');
    if ($nid) {
      $node = Node::load($nid);
      $bundle = $node->bundle();
      $type = NodeType::load($bundle);
      $view_count_enabled = (bool) $type->getThirdPartySetting('vactory_node_view_count', 'enabling_count_node', '');
      if (!$view_count_enabled) {
        return new JsonResponse([
          'message' => $this->t("Views count is not enabled for {$bundle}"),
          'code' => -3,
        ], 400);
      }
      if ($node instanceof NodeInterface) {
        if ($node->hasField('field_node_count_view')) {
          $count = isset($node->get('field_node_count_view')->getValue()[0]) ? $node->get('field_node_count_view')->getValue()[0]['value'] : 0;
          $count++;
          $node->set('field_node_count_view', $count);
          $node->save();
          return new JsonResponse([
            'message' => $this->t('Node count has been incremented'),
            'count' => $node->get('field_node_count_view')->getValue()[0]['value'],
            'code' => 1,
          ]);
        }
        return new JsonResponse([
          'message' => $this->t('The specific node has no field (count views)!'),
          'code' => -1,
        ], 400);
      }
    }

    return new JsonResponse([
      'message' => $this->t('Invalid node id!'),
      'code' => -2,
    ], 400);
  }

}
