<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Duplicate Paragraph Endpoint.
 */
class DuplicateParagraphController extends ControllerBase {

  /**
   * Duplicate paragraphs.
   */
  public function duplicate(Request $request) {
    // Validate user permission.
    $this->checkUserPermission();
    $body = $this->parseRequestBody($request);
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
      throw new NotFoundHttpException('Cannot get target paragraph.');
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

    // Load and validate node.
    $node = $this->loadNode($nid);
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
   * Delete paragraph.
   */
  public function delete(Request $request) {
    try {
      $this->checkUserPermission();

      $body = $this->parseRequestBody($request);
      $paragraph_id = $body['paragraph_id'] ?? NULL;
      $nid = $body['nid'] ?? NULL;

      $node = $this->loadNode($nid);

      $paragraphs = $node->get('field_vactory_paragraphs')->getValue() ?? [];
      $updated_paragraphs = $this->removeParagraph($paragraphs, $paragraph_id);

      $node->set('field_vactory_paragraphs', $updated_paragraphs);
      $node->save();

      clear_next_cache();

      return new JsonResponse([
        'status' => TRUE,
        'message' => $this->t('Paragraph successfully deleted.'),
      ], 200);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $e->getMessage(),
      ], $e->getCode() ?: 400);
    }
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

  /**
   * Check if user has live mode permission.
   */
  private function checkUserPermission() {
    $user = $this->currentUser();
    if (!$user->hasPermission('edit content live mode')) {
      throw new AccessDeniedHttpException('Edit content live mode permission is required.');
    }
  }

  /**
   * Parse request body.
   */
  private function parseRequestBody(Request $request) {
    $content = $request->getContent();
    $body = json_decode($content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new BadRequestHttpException('Invalid JSON payload.');
    }
    return $body;
  }

  /**
   * Load Node by id.
   */
  private function loadNode($nid) {
    $node = Node::load($nid);
    if (!$node) {
      throw new NotFoundHttpException('Cannot find target node.');
    }
    return $node;
  }

  /**
   * Remove paragraph.
   */
  private function removeParagraph(array $paragraphs, int $paragraph_id) {
    $updated_paragraphs = array_filter($paragraphs, function ($paragraph) use ($paragraph_id) {
      return $paragraph['target_id'] != $paragraph_id;
    });
    if (count($updated_paragraphs) === count($paragraphs)) {
      throw new NotFoundHttpException('Cannot find target paragraph.');
    }
    return array_values($updated_paragraphs);
  }

}
