<?php

namespace Drupal\vactory_decoupled_flag\Services;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_decoupled\BlocksManager;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class.
 */
class VactoryDecoupledFlagService
{

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   *  * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */

  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, Connection $database)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->database = $database;
  }

  /**
   * Check whether the current user flagged nodes.
   */
  public function isCurrentUserFlaggedNode($entity): bool
  {

    $ids = $this->entityTypeManager->getStorage('flagging')->getQuery()
      ->condition('flag_id', 'default_flag')
      ->condition('uid', $this->currentUser->id())
      ->condition('entity_id', $entity->id())
      ->accessCheck(FALSE)
      ->execute();

    return !empty($ids);
  }

  /**
   * Get Flagged nodes.
   */
  public function getFlaggedNodes($bundle)
  {
    $args = [
      ':bundle' => $bundle,
      ':user' => $this->currentUser->id(),
      ':flag' => 'default_flag',
    ];
    $sql = "SELECT entity_id AS id FROM flagging AS f ";
    $sql .= "JOIN node_field_data AS n ON n.nid = f.entity_id ";
    if ($bundle != 'all'){
      $sql .= "WHERE n.type=:bundle ";
    }
    $sql .= "AND f.uid=:user ";
    $sql .= "AND f.flag_id=:flag ";
    $results = $this->database->query($sql, $args)->fetchAllAssoc('id');
    return array_keys($results);
  }

}
