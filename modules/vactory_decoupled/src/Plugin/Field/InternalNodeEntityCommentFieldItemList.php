<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Comment per node.
 */
class InternalNodeEntityCommentFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->hasField('comment')) {
      $roles = Role::loadMultiple();
      $comments = !empty($entity->get('comment')->getValue()) ? $entity->get('comment')->getValue()[0] : [];
      $contributions = isset($comments['comment_count']) ? $comments['comment_count'] : 0;
      $last_contribution = $contributions > 0 ? $comments['last_comment_timestamp'] : null;
      $settings = $entity->get('comment')->getSettings();
      $settings['status'] = $entity->get('comment')->status;

      foreach ($roles as $role) {
        $settings['roles'][$role->id()]['post_comment'] = $role->hasPermission('post comments');
        $settings['roles'][$role->id()]['view_comments'] = $role->hasPermission('access comments');
        $settings['roles'][$role->id()]['skip_comment_approval'] = $role->hasPermission('skip comment approval');
      }

      $value = [
        'contributions' => $contributions,
        'last_contribution' => $last_contribution,
        'settings' => $settings,
      ];
    }
    else {
      $value = [
        'error' => 'Entity should have a field named comment'
      ];
    }

    $this->list[0] = $this->createItem(0, $value);
  }
}
