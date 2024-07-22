<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;
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

    $paragraph_query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $paragraph_query->accessCheck(FALSE);

    $is_multiple_paragraph = isset($body['paragraphTabId']) && isset($body['templateDelta']);
    $result = NULL;
    if (!$is_multiple_paragraph) {
      $result = $this->handleParagraphComponent($paragraph_query, $body);
    }
    else {
      $result = $this->handleParagraphMultiple($paragraph_query, $body);
    }

    if (is_array($result) && isset($result['code']) && isset($result['message'])) {
      return new JsonResponse([
        'message' => $result['message'],
      ], $result['code']);
    }

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('Field updated !'),
    ], 200);

  }

  /**
   * Edit component paragraph data.
   */
  private function handleParagraphComponent($paragraph_query, $body) {
    $paragraph_query->condition('id', $body['paragraphId'])
      ->condition('parent_id', $body['nid'])
      ->condition('parent_type', 'node')
      ->condition('type', 'vactory_component');

    $result = $this->fetchParagraph($paragraph_query);
    if (!$result instanceof Paragraph) {
      return $result;
    }
    $paragraph = $result;
    $component = $paragraph->get('field_vactory_component')->getValue();
    $component_data = json_decode($component[0]['widget_data'], TRUE);
    $edited = $this->editData($component_data, $body['id'], $body['content']);
    if (!$edited) {
      return [
        'status' => 400,
        'message' => $this->t('Cannot find concerned field'),
      ];
    }
    $component_data = json_encode($component_data);
    $component[0]['widget_data'] = $component_data;
    $paragraph->field_vactory_component = $component;
    $paragraph->setNewRevision(TRUE);
    $paragraph->save();
    $last_paragraph_revision = $paragraph->getRevisionId();
    $node = $this->entityTypeManager()->getStorage('node')->load($body['nid']);
    $node = $this->getNodeTranslation($node);

    if (is_null($node)) {
      return [
        'status' => 400,
        'message' => $this->t('No translation founded for this page'),
      ];
    }

    $node_paragraphs = $node->get('field_vactory_paragraphs')->getValue();
    foreach ($node_paragraphs as &$item) {
      if ($item['target_id'] == $body['paragraphId']) {
        $item['target_revision_id'] = $last_paragraph_revision;
        break;
      }
    }
    unset($item);
    $node->set('field_vactory_paragraphs', $node_paragraphs);

    $node->setNewRevision(TRUE);
    $node->revision_log = 'Update from live mode : ' . json_encode($body);
    $node->setRevisionCreationTime(time());
    $node->setRevisionUserId(\Drupal::currentUser()->id());
    $node->set('revision_translation_affected', TRUE);
    $node->save();

  }

  /**
   * Edit multiple paragraph data.
   */
  private function handleParagraphMultiple($paragraph_query, $body) {
    $paragraph_query->condition('id', $body['paragraphTabId'])
      ->condition('parent_id', $body['paragraphId'])
      ->condition('parent_type', 'paragraph')
      ->condition('type', 'vactory_paragraph_tab');

    $result = $this->fetchParagraph($paragraph_query);
    if (!$result instanceof Paragraph) {
      return $result;
    }
    $paragraph = $result;
    $tab = $paragraph->get('field_tab_templates')->getValue();
    $tab_data = json_decode($tab[$body['templateDelta']]['widget_data'], TRUE);
    $edited = $this->editData($tab_data, $body['id'], $body['content']);
    if (!$edited) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot find concerned field'),
      ];
    }
    $tab_data = json_encode($tab_data);
    $tab[$body['templateDelta']]['widget_data'] = $tab_data;
    $paragraph->field_tab_templates = $tab;
    // Saving the tab paragraph.
    $paragraph->setNewRevision(TRUE);
    $paragraph->save();
    $last_paragraph_revision = $paragraph->getRevisionId();

    // Loading the main paragraph.
    $main_paragraph_query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $main_paragraph_query->accessCheck(FALSE);
    $main_paragraph_query->condition('id', $body['paragraphId'])
      ->condition('parent_id', $body['nid'])
      ->condition('parent_type', 'node')
      ->condition('type', 'vactory_paragraph_multi_template');

    $result = $this->fetchParagraph($main_paragraph_query);
    if (!$result instanceof Paragraph) {
      return $result;
    }
    $main_paragraph = $result;
    $main_paragraph_tabs = $main_paragraph->get('field_vactory_paragraph_tab')->getValue();
    foreach ($main_paragraph_tabs as &$item) {
      if ($item['target_id'] == $body['paragraphTabId']) {
        $item['target_revision_id'] = $last_paragraph_revision;
        break;
      }
    }
    unset($item);

    $main_paragraph->set('field_vactory_paragraph_tab', $main_paragraph_tabs);
    $main_paragraph->setNewRevision(TRUE);
    $main_paragraph->save();
    $last_main_paragraph_revision = $main_paragraph->getRevisionId();

    $node = $this->entityTypeManager()->getStorage('node')->load($body['nid']);
    $node = $this->getNodeTranslation($node);

    if (is_null($node)) {
      return [
        'status' => 400,
        'message' => $this->t('No translation founded for this page'),
      ];
    }

    $node_paragraphs = $node->get('field_vactory_paragraphs')->getValue();
    foreach ($node_paragraphs as &$item) {
      if ($item['target_id'] == $body['paragraphId']) {
        $item['target_revision_id'] = $last_main_paragraph_revision;
        break;
      }
    }
    unset($item);
    $node->set('field_vactory_paragraphs', $node_paragraphs);

    $node->setNewRevision(TRUE);
    $node->revision_log = 'Update from live mode : ' . json_encode($body);
    $node->setRevisionCreationTime(time());
    $node->setRevisionUserId(\Drupal::currentUser()->id());
    $node->set('revision_translation_affected', TRUE);
    $node->save();
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

  /**
   * Edit DF component.
   */
  private function fetchParagraph($paragraph_query) {
    $res = $paragraph_query->execute();
    $language_manager = $this->languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();
    $default_language = $language_manager->getDefaultLanguage()->getId();

    if (count($res) !== 1) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot get target paragraph'),
      ];
    }
    $paragraph_id = reset($res);
    $paragraph = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph_id);
    if ($current_language == $default_language) {
      return $paragraph;
    }
    elseif ($paragraph->hasTranslation($current_language)) {
      return $paragraph->getTranslation($current_language);
    }
    else {
      return [
        'code' => 400,
        'message' => $this->t('No translation founded'),
      ];
    }
  }

  /**
   * Retrieves the translation of a node for the current language.
   */
  private function getNodeTranslation($node) {
    $language_manager = $this->languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();
    $default_language = $language_manager->getDefaultLanguage()->getId();
    if ($current_language == $default_language) {
      return $node;
    }
    elseif ($node->hasTranslation($current_language)) {
      return $node->getTranslation($current_language);
    }
    else {
      return NULL;
    }
  }

}
