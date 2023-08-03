<?php

namespace Drupal\vactory_academy\Services;

use Drupal\Core\Cache\CacheableMetadata;
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
class AcademyFlagService {

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
   * {@inheritDoc}
   *  * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */

  public  function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser  )
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Check whether the current user flagged nodes.
   */
  public function isCurrentUserFlaggedNode ($entity): bool {

    $ids = $this->entityTypeManager->getStorage('flagging')->getQuery()
        ->condition('flag_id', 'favorite_academy')
        ->condition('uid', $this->currentUser->id())
        ->condition('entity_id', $entity->id())
        ->accessCheck(FALSE)
        ->execute();

    return !empty($ids);
  }

 /**
   * Get Flagged nodes.
  */
  public function getFlaggedNodes() {
    $favourites = $this->entityTypeManager->getStorage('flagging')->loadByProperties(['uid' => $this->currentUser->id()]);
    $nids = [];
    if (isset($favourites) && !empty($favourites)) {
      foreach($favourites  as $favourite ) {
      $nids[] = $favourite->getFlaggableId();
      }
    }
    return $nids;
  }

}
