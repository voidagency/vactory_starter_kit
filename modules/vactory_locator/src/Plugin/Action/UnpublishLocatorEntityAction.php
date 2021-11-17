<?php

namespace Drupal\vactory_locator\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Content moderation publish node.
 *
 * @Action(
 *   id = "locator_entity_unpublish_action",
 *   label = @Translation("Unpublish locator entity"),
 *   type = "locator_entity",
 *   confirm = FALSE
 * )
 */
class UnpublishLocatorEntityAction extends ViewsBulkOperationsActionBase {

  /**
   * Give unpublish action to everyone.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * Execute unpublish action.
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    if ($status = $entity->get('status')->value) {
      $entity->set('status', 0)
        ->save();
    }
    return $this->t(':title has been unpublished',
      [
        ':title' => $entity->getName(),
      ]
    );
  }

}
