<?php

namespace Drupal\vactory_revisions\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Vactory revisions service.
 */
class VactoryRevisionsService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Vactory revision service constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create new revision.
   */
  public function vactoryCreateRevisions(EntityInterface &$entity, $operation) {
    $excluded_entities = [
      'vactory_revision',
      'notifications_entity',
    ];
    if (
      in_array($entity->getEntityTypeId(), $excluded_entities) ||
      empty($operation) ||
      ($operation === 'update' && $entity->isNew())
    ) {
      return;
    }
    $current_user_id = \Drupal::currentUser()->id();
    if ($operation !== 'delete') {
      $entity->set('last_contributor', $current_user_id);
    }
    $revision = [
      'op_type' => $operation,
      'op_time' => time(),
      'user_id' => $current_user_id,
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
    ];
    $this->entityTypeManager->getStorage('vactory_revision')
      ->create($revision)
      ->save();
  }
}