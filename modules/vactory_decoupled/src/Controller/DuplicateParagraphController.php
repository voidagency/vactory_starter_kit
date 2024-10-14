<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Duplicate Paragraph Endpoint.
 */
class DuplicateParagraphController extends ControllerBase {

  /**
   * Duplicate paragraphs.
   */
  public function duplicate(Request $request) {
    $user_id = \Drupal::currentUser()->id();
    $user = $this->entityTypeManager()->getStorage('user')->load($user_id);
    $user_granted = $user->hasPermission('edit content live mode');
    if (!$user_granted) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $this->t('edit content live mode permission is required'),
      ], 400);
    }
    $body = json_decode($request->getContent(), TRUE);
    $paragraph_id = $body['paragraph_id'] ?? NULL;
    $nid = $body['nid'] ?? NULL;
    $weight = $body['weight'] ?? 0;
    $parent_id = $body['parent_id'] ?? NULL;
    $paragraph_query = $this->entityTypeManager()
      ->getStorage('paragraph')
      ->getQuery();
    $paragraph_query->accessCheck(FALSE);
    $paragraph_query->condition('id', $paragraph_id)
      ->condition('parent_id', $parent_id)
      ->condition('parent_type', 'node')
      ->condition('type', "vactory_component");

    $res = $paragraph_query->execute();
    if (count($res) !== 1) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot get target paragraph'),
      ];
    }
    $paragraph_id = reset($res);
    $paragraph = $this->entityTypeManager()
      ->getStorage('paragraph')
      ->load($paragraph_id);

    // Check if the replicate module is enabled.
    if (\Drupal::hasService('replicate.replicator')) {
      $duplicate_entity = \Drupal::getContainer()
        ->get('replicate.replicator')
        ->cloneEntity($paragraph);
    }
    else {
      $duplicate_entity = $paragraph->createDuplicate();
    }
    $duplicate_entity->save();

    // Add translation for this paragraph.
    foreach ($this->languageManager()->getLanguages() as $language) {
      if (!$duplicate_entity->hasTranslation($language->getId())) {
        $duplicate_entity->addTranslation($language->getId(), $duplicate_entity->toArray());
        $duplicate_entity->save();
      }
    }

    $node = Node::load($nid);
    if (!isset($node)) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot get target node.'),
      ];
    }
    $paragraphs = $node->field_vactory_paragraphs->getValue() ?? [];
    $this->insertByWeight($paragraphs, [
      'target_id' => $duplicate_entity->id(),
      'target_revision_id' => $duplicate_entity->getRevisionId(),
    ], $weight);

    $node->set('field_vactory_paragraphs', $paragraphs);
    $node->save();
    clear_next_cache();

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('Paragraph duplicated !'),
      'paragraph' => $this->prepareComponentData($duplicate_entity),
    ], 200);
  }

  /**
   * Prepare component data.
   */
  private function prepareComponentData($entity) {
    $data = $entity->get("field_vactory_component")->getValue();
    $processed_paragraph = \Drupal::service('vactory_decoupled.dynamic_field_manager')
      ->process($data[0]);
    return [
      "id" => $entity->id(),
      "data" => $processed_paragraph['data'] ?? NULL,
      "title" => $entity->get('field_vactory_title')->getValue()[0]['value'] ?? "",
      "flag" => $entity->get('field_vactory_flag')->getValue()[0]['value'] ?? "",
    ];
  }

  /**
   * Insert item in the given weight.
   */
  private function insertByWeight(&$items, $newValue, $newWeight) {
    $newItems = [];
    $inserted = FALSE;
    $multiplier = 1000;

    $newWeight *= $multiplier;
    foreach ($items as $key => $value) {
      $key *= $multiplier;
      if (!$inserted && $newWeight <= $key) {
        $newItems[$newWeight] = $newValue;
        $inserted = TRUE;
      }

      $newItems[$key + ($inserted ? $multiplier : 0)] = $value;
    }

    if (!$inserted) {
      $newItems[$newWeight] = $newValue;
    }

    ksort($newItems);
    $items = array_combine(
      array_map(function ($k) use ($multiplier) {
        return $k / $multiplier;
      }, array_keys($newItems)),
      array_values($newItems)
    );
  }

}
