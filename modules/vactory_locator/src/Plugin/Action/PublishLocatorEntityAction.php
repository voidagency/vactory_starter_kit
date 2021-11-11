<?php

namespace Drupal\vactory_locator\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Content moderation publish node.
 *
 * @Action(
 *   id = "locator_entity_publish_action",
 *   label = @Translation("Publish locator entity"),
 *   type = "locator_entity",
 *   confirm = FALSE
 * )
 */
class PublishLocatorEntityAction extends ViewsBulkOperationsActionBase {

  /**
   * Give action access to everyone.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * Execute publish action.
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    if (!$status = $entity->get('status')->value) {
      $entity->setPublished(TRUE)
        ->save();
    }
    return $this->t(':title has been published',
      [
        ':title' => $entity->getName(),
      ]
    );
  }

}
