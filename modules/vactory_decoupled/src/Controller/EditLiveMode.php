<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Edit Live Mode Endpoint.
 */
class EditLiveMode extends ControllerBase {

  /**
   * Edit live mode.
   */
  public function edit(Request $request) {
    $body = json_decode($request->getContent(), TRUE);
    $paragraph_query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $paragraph_query->accessCheck(FALSE)
      ->condition('id', $body['paragraphId'])
      ->condition('parent_id', $body['nid'])
      ->condition('parent_type', 'node')
      ->condition('type', 'vactory_component');
    $res = $paragraph_query->execute();

    if (count($res) !== 1) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $this->t('Cannot get target paragraph'),
      ], 400);
    }
    $paragraph_id = reset($res);
    $paragraph = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph_id);
    $component = $paragraph->get('field_vactory_component')->getValue();
    $component_data = json_decode($component[0]['widget_data'], TRUE);
    $edited = $this->editData($component_data, $body['id'], $body['content']);
    if (!$edited) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $this->t('Cannot find concerned field'),
      ], 400);
    }
    $component_data = json_encode($component_data);
    $component[0]['widget_data'] = $component_data;
    $paragraph->field_vactory_component = $component;
    $paragraph->save();

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('Field updated !'),
    ], 200);
  }

  /**
   * Edit DF component.
   */
  private function editData(array &$data, string $keyString, string $newValue) {
    $keys = explode('.', $keyString);
    $current = &$data;

    foreach ($keys as $key) {
      if (isset($current[$key])) {
        $current = &$current[$key];
      }
      else {
        return FALSE;
      }
    }
    $current = $newValue;
    return TRUE;
  }

}
