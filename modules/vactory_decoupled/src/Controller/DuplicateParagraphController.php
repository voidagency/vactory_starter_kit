<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\vactory_dynamic_field_dummy\Services\GenerateDummyPageService;
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
    $is_first = $body['is_first'] ?? FALSE;
    $type = $body['type'] ?? "paragraph--vactory_component";
    $bundle = $body['bundle'] ?? "node--vactory_page";
    $paragraph_query = $this->entityTypeManager()
      ->getStorage('paragraph')
      ->getQuery();
    $paragraph_query->accessCheck(FALSE);
    $paragraph_query->condition('id', $paragraph_id)
      ->condition('parent_id', $parent_id)
      ->condition('parent_type', 'node')
      ->condition('type', str_replace('paragraph--', '', $type));

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
    if ($is_first) {
      $paragraphs = [
        [
          'target_id' => $duplicate_entity->id(),
          'target_revision_id' => $duplicate_entity->getRevisionId(),
        ],
        ...$paragraphs,
      ];
    }
    else {
      $this->insertByWeight($paragraphs, [
        'target_id' => $duplicate_entity->id(),
        'target_revision_id' => $duplicate_entity->getRevisionId(),
      ], $weight);
    }

    $node->set('field_vactory_paragraphs', $paragraphs);
    $node->save();
    clear_next_cache();

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('Paragraph duplicated !'),
      'paragraphs' => $this->prepareComponentData($nid, $bundle)['data'] ?? [],
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
      $bundle = $body['bundle'] ?? "node--vactory_page";

      $node = $this->loadNode($nid);

      $paragraphs = $node->get('field_vactory_paragraphs')->getValue() ?? [];
      $updated_paragraphs = $this->removeParagraph($paragraphs, $paragraph_id);

      $node->set('field_vactory_paragraphs', $updated_paragraphs);
      $node->save();

      clear_next_cache();

      return new JsonResponse([
        'status' => TRUE,
        'message' => $this->t('Paragraph successfully deleted.'),
        'paragraphs' => $this->prepareComponentData($nid, $bundle)['data'] ?? [],
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
   * Get list of widgets.
   */
  public function list(Request $request) {
    try {
      $this->checkUserPermission();
      $vactoryProviderManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
      $widgets_list = $vactoryProviderManager->getModalWidgetsList([]);
      $widgets_list = array_map(function ($widgets) {
        $results = [];
        foreach ($widgets as $widget) {
          $item = [];
          $item['id'] = $widget['uuid'];
          $item['title'] = $widget['name'];
          $item['imageUrl'] = $widget['screenshot'];
          array_push($results, $item);
        }
        return $results;
      }, $widgets_list);
      return new JsonResponse([
        'widgets' => $widgets_list ?? [],
      ], 200);
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_decoupled')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * Create new paragraph.
   */
  public function new(Request $request) {
    try {
      $this->checkUserPermission();
      $body = $this->parseRequestBody($request);
      $nid = $body['nid'] ?? NULL;
      $widget_id = $body['widget_id'] ?? NULL;
      $weight = $body['weight'] ?? 0;
      $is_first = $body['is_first'] ?? FALSE;
      $bundle = $body['bundle'] ?? 'node--vactory_page';
      $vactoryProviderManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
      $widget = $vactoryProviderManager->loadSettings($widget_id);
      $widget_data = GenerateDummyPageService::prepareContent($widget);
      $paragraph = GenerateDummyPageService::createParagraph($widget_id, $widget_data);

      foreach ($this->languageManager()->getLanguages() as $language) {
        if (!$paragraph->hasTranslation($language->getId())) {
          $paragraph->addTranslation($language->getId(), $paragraph->toArray());
          $paragraph->save();
        }
      }
      $inserted_paragraph = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => \Drupal::entityTypeManager()
          ->getStorage('paragraph')
          ->getLatestRevisionId($paragraph->id()),
      ];
      // Load and validate node.
      $node = $this->loadNode($nid);
      $paragraphs = $node->field_vactory_paragraphs->getValue() ?? [];
      if ($is_first) {
        $paragraphs = [
          $inserted_paragraph,
          ...$paragraphs,
        ];
      }
      else {
        $this->insertByWeight($paragraphs, $inserted_paragraph, $weight);
      }

      $node->set('field_vactory_paragraphs', $paragraphs);
      $node->save();
      clear_next_cache();
      return new JsonResponse([
        'status' => TRUE,
        'message' => $this->t('Paragraph duplicated !'),
        'paragraphs' => $this->prepareComponentData($nid, $bundle)['data'] ?? [],
      ], 200);
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_decoupled')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * Reorder the paragraphs.
   */
  public function reorder(Request $request) {
    try {
      $this->checkUserPermission();
      $body = $this->parseRequestBody($request);
      $nid = $body['nid'] ?? NULL;
      $bundle = $body['bundle'] ?? 'node--vactory_page';
      $paragraphs_ids = $body['paragraphs_ids'] ?? [];

      // Load and validate node.
      $node = $this->loadNode($nid);
      $paragraphs = $node->field_vactory_paragraphs->getValue() ?? [];

      $reordered_paragraphs = [];
      foreach ($paragraphs_ids as $pid) {
        $result = array_filter($paragraphs, function ($paragraph) use ($pid) {
          return $paragraph['target_id'] == $pid;
        });
        if ($result) {
          $reordered_paragraphs[] = reset($result);
        }
      }

      $node->set('field_vactory_paragraphs', $reordered_paragraphs);
      $node->save();
      clear_next_cache();
      return new JsonResponse([
        'status' => TRUE,
        'message' => $this->t('Paragraph reordered successfully.'),
        'paragraphs' => $this->prepareComponentData($nid, $bundle)['data'] ?? [],
      ], 200);
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_decoupled')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * Prepare component data.
   */
  private function prepareComponentData($nid, $bundle = 'node--vactory_page') {
    $config = [
      'resource' => $bundle,
      'filters' => [
        'filter[drupal_internal__nid]=' . $nid,
        'fields[node--vactory_page]=field_vactory_paragraphs',
        'fields[paragraph--vactory_component]=drupal_internal__id,paragraph_section,paragraph_identifier,paragraph_container,field_animation,container_spacing,paragraph_css_class,paragraph_background_color,paragraph_background_image,field_vactory_component,field_vactory_title,field_background_color,field_paragraph_hide_lg,field_paragraph_hide_sm,field_position_image_x,field_position_image_y,field_size_image,field_vactory_flag,field_vactory_flag_2,paragraph_background_parallax',
        'fields[media--image]=thumbnail',
        'fields[file--image]=uri',
        'include=field_vactory_paragraphs,field_vactory_paragraphs.field_vactory_paragraph_tab,field_vactory_paragraphs.paragraph_background_image,field_vactory_paragraphs.paragraph_background_image.thumbnail',
      ],
    ];
    $json_api_generator_service = \Drupal::service('vactory_decoupled.jsonapi.generator');
    return $json_api_generator_service->fetch($config);
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
    $user = User::load($user->id());
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
