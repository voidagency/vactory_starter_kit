<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\Entity\File;
use Drupal\locale\StringDatabaseStorage;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edit Live Mode Endpoint.
 */
class EditLiveMode extends ControllerBase {

  /**
   * Language manager service.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $stringDatabaseStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    StringDatabaseStorage $stringDatabaseStorage,
    FileSystemInterface $file_system
  ) {
    $this->stringDatabaseStorage = $stringDatabaseStorage;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('locale.storage'),
      $container->get('file_system')
    );
  }

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

    if (isset($body['type']) && $body['type'] === 'i18n') {
      $result = $this->handleI18n($body);
      if (is_array($result) && isset($result['code']) && isset($result['message'])) {
        return new JsonResponse([
          'message' => $result['message'],
        ], $result['code']);
      }
    }
    elseif ($res = $this->isDfBlock($body['id'])) {
      $result = $this->handleDfBlock($res, $body);
      if (is_array($result) && isset($result['code']) && isset($result['message'])) {
        return new JsonResponse([
          'message' => $result['message'],
        ], $result['code']);
      }
    }
    elseif ($res = $this->isParagraphTitle($body['id'])) {
      $result = $this->handleParagraphTitle($res, $body);
      if (is_array($result) && isset($result['code']) && isset($result['message'])) {
        return new JsonResponse([
          'message' => $result['message'],
        ], $result['code']);
      }
    }
    else {
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
    $this->handleNodeRevision($body, $body['paragraphId'], $last_paragraph_revision);
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
    $this->handleNodeRevision($body, $body['paragraphId'], $last_main_paragraph_revision);
  }

  /**
   * Edit DF component.
   */
  private function editData(array &$data, string $keyString, $newValue) {
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

  /**
   * Handle i18n translatyion edit.
   */
  private function handleI18n($body) {
    $string = $this->stringDatabaseStorage->findString([
      'source' => $body['id'],
      'context' => '_FRONTEND',
    ]);

    if (is_null($string)) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot get key:') . ' ' . $body['id'],
      ];
    }

    $this->stringDatabaseStorage->createTranslation([
      'lid' => $string->lid,
      'language' => $this->languageManager()->getCurrentLanguage()->getId(),
      'translation' => $body['content'],
    ])->save();

    return [
      'code' => 200,
      'message' => $this->t("Translation updated"),
    ];

  }

  /**
   * Edit live mode image.
   */
  public function editImage(Request $request) {
    $user_id = \Drupal::currentUser()->id();
    $user = $this->entityTypeManager()->getStorage('user')->load($user_id);
    $user_granted = $user->hasPermission('edit content live mode');

    if (!$user_granted) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $this->t('edit content live mode permission is required'),
      ], 400);
    }
    $file = $request->files->get('file');
    $body = $request->request->all();
    if (!$file instanceof UploadedFile) {
      return new JsonResponse([
        'error' => 'No file uploaded',
      ], Response::HTTP_BAD_REQUEST);
    }

    // Move the file to Drupal's public file system.
    $directory = PublicStream::basePath() . '/live-mode-images';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $file->move($directory, $file->getClientOriginalName());

    // Create the file entity.
    $file_entity = File::create([
      'uri' => 'public://live-mode-images/' . $file->getClientOriginalName(),
    ]);
    $file_entity->save();

    // Create the media entity.
    $media = Media::create([
      'bundle' => 'image',
      'name' => $file->getClientOriginalName(),
      'status' => 1,
      'field_media_image' => [
        'target_id' => $file_entity->id(),
        'alt' => $file->getClientOriginalName(),
        'title' => $file->getClientOriginalName(),
      ],
    ]);
    $media->save();

    $paragraph_query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $paragraph_query->accessCheck(FALSE);

    $is_multiple_paragraph = isset($body['paragraphTabId']) && isset($body['templateDelta']);

    $body['content'] = [
      uniqid() => [
        'selection' => [
          [
            'target_id' => $media->id(),
          ],
        ],
      ],
    ];
    if ($res = $this->isDfBlock($body['id'])) {
      $result = $this->handleDfBlock($res, $body);
      if (is_array($result) && isset($result['code']) && isset($result['message'])) {
        return new JsonResponse([
          'message' => $result['message'],
        ], $result['code']);
      }
    }
    elseif (!$is_multiple_paragraph) {
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
      'message' => $this->t('Image updated !'),
    ], 200);

  }

  /**
   * Validates the given ID and extracts the block ID and the ID if valid.
   *
   * The function checks if the input string is in the format "block:id|value",
   * where:
   * - "block:id" must be in the format `block:number` (e.g., `block:123`).
   * - The second part after the `|` can be any string.
   *
   * If the format is valid, the function returns an array containing:
   * - 'block_id': The extracted numeric ID from "block:id".
   * - 'id': The second part of the string after the `|`.
   *
   * Otherwise, it returns `FALSE`.
   *
   * @param string $id
   *   The ID string to validate and extract from.
   *
   * @return mixed
   *   An array with 'block_id' and 'id' if valid,
   *   or `FALSE` if the format is invalid.
   */
  private function isDfBlock(string $id) {
    // Check if the ID contains two parts separated by "|".
    $parts = explode('|', $id);

    // Ensure there are exactly two parts.
    if (count($parts) !== 2) {
      return FALSE;
    }

    // Check if the first part is in the format block:id.
    if (preg_match('/^block:(\d+)$/', $parts[0], $matches)) {
      // Extract the id from the first part.
      return [
        'block_id' => $matches[1],
        'id' => $parts[1],
      ];
    }

    return FALSE;
  }

  /**
   * Handles the dynamic block update by processing the provided data.
   *
   * The function loads the block content specified by `block_id`, retrieves its
   * components, and updates the relevant part based on the provided data.
   * It returns error if the block is not found or the target field is missing.
   *
   * @param array $res
   *   An array containing the 'block_id' and the specific 'id' to be edited.
   * @param array $body
   *   An array containing the data to be used for updating the block.
   *
   * @return array
   *   An associative array with a status 'code' and a 'message'.
   *   - 'code' => 200 if the update was successful, 400 otherwise.
   *   - 'message' => A message indicating the result of the operation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function handleDfBlock(array $res, array $body) {
    $block = BlockContent::load($res['block_id']);
    if (!$block) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot find target block !'),
      ];
    }

    $language_manager = $this->languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();
    $default_language = $language_manager->getDefaultLanguage()->getId();

    if ($current_language !== $default_language && $block->hasTranslation($current_language)) {
      $block = $block->getTranslation($current_language);
    }

    $component = $block->get('field_dynamic_block_components')->getValue();
    $component_data = json_decode($component[0]['widget_data'], TRUE);
    $edited = $this->editData($component_data, $res['id'], $body['content']);
    if (!$edited) {
      return [
        'code' => 400,
        'message' => $this->t('Cannot find concerned field'),
      ];
    }

    $component_data = json_encode($component_data);
    $component[0]['widget_data'] = $component_data;
    $block->field_dynamic_block_components = $component;
    $block->setNewRevision(TRUE);
    $block->revision_log = 'Update from live mode : ' . json_encode($body);
    $block->setRevisionCreationTime(time());
    $block->setRevisionUserId(\Drupal::currentUser()->id());
    $block->set('revision_translation_affected', TRUE);
    $block->save();

    return [
      'code' => 200,
      'message' => $this->t('Block updated !'),
    ];
  }

  /**
   * Validates the given ID string and extracts the numeric ID if valid.
   *
   * The function checks if the input string matches the format
   * "paragraph_title|{$id}", where "{$id}" is a numeric identifier.
   *
   * If the format is valid, the function returns the extracted numeric ID.
   * Otherwise, it returns `false`.
   *
   * @param string $id
   *   The ID string to validate and extract from.
   *
   * @return mixed
   *   The extracted numeric ID if valid, or `false` if the format is invalid.
   */
  private function isParagraphTitle(string $id) {
    // Check if the ID matches the required format.
    if (preg_match('/^paragraph_title\|(\d+)$/', $id, $matches)) {
      // Return the extracted ID.
      return $matches[1];
    }

    // Return false if the format is invalid.
    return FALSE;
  }

  /**
   * Handles the update of a paragraph's title.
   *
   * This function updates the title of a paragraph associated with a specific
   * node. It retrieves the paragraph, sets a new title, creates a new revision,
   * and saves the changes. After updating the paragraph, it also handles the
   * node revision associated with the updated paragraph.
   *
   * @param int $res
   *   The ID of the paragraph to update.
   * @param array $body
   *   An associative array containing the node ID ('nid') and
   *   the new content ('content') for the paragraph title.
   *
   * @return array
   *   An associative array with a status code
   *   and message indicating the result of the operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function handleParagraphTitle($res, array $body) {
    $paragraph_query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $paragraph_query
      ->accessCheck(FALSE)
      ->condition('id', $res)
      ->condition('parent_id', $body['nid'])
      ->condition('parent_type', 'node')
      ->condition('type', 'vactory_component');

    $result = $this->fetchParagraph($paragraph_query);
    if (!$result instanceof Paragraph) {
      return $result;
    }
    $paragraph = $result;
    $paragraph->set('field_vactory_title', $body['content']);
    $paragraph->setNewRevision(TRUE);
    $paragraph->save();

    $last_paragraph_revision = $paragraph->getRevisionId();
    $this->handleNodeRevision($body, $res, $last_paragraph_revision);

    return [
      'code' => 200,
      'message' => $this->t('Paragraph title updated !'),
    ];

  }

  /**
   * Handles the creation of a new node revision after a paragraph update.
   *
   * This function updates the node's paragraph field to point to the new
   * revision of the updated paragraph. It then creates and saves a new revision
   * of the node.
   *
   * @param array $body
   *   An associative array containing the node ID
   *   ('nid') and additional data for the node.
   * @param int $paragraphId
   *   The ID of the paragraph that was updated.
   * @param int $lastParagraphRevision
   *   The ID of the latest paragraph revision.
   *
   * @return array|void
   *   An associative array with a 'status' and 'message' indicating
   *   the result of the operation. 'status' is 400 if the node is
   *   not found or has no translation; otherwise, it returns void.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function handleNodeRevision(array $body, $paragraphId, $lastParagraphRevision) {
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
      if ($item['target_id'] == $paragraphId) {
        $item['target_revision_id'] = $lastParagraphRevision;
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

}
