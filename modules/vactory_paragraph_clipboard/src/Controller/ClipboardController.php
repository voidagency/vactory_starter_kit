<?php

namespace Drupal\vactory_paragraph_clipboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Controller for paragraph clipboard operations.
 */
class ClipboardController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ClipboardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Handles the paste operation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Ajax response.
   */
  public function paste(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['paragraph_id']) || empty($data['node_id'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Invalid data provided',
        ], JsonResponse::HTTP_BAD_REQUEST);
      }

      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
      $paragraph = $paragraph_storage->load($data['paragraph_id']);

      if (!$paragraph) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Paragraph not found',
        ], JsonResponse::HTTP_NOT_FOUND);
      }

      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->load($data['node_id']);

      if (!$node) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Node not found',
        ], JsonResponse::HTTP_NOT_FOUND);
      }

      $cloned_paragraph = $paragraph->createDuplicate();
      $cloned_paragraph->save();

      $node->get('field_vactory_paragraphs')->appendItem($cloned_paragraph);
      $node->save();

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Paragraph successfully cloned',
        'paragraph_id' => $cloned_paragraph->id(),
      ], JsonResponse::HTTP_OK);

    }
    catch (\Exception $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
