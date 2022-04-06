<?php

namespace Drupal\vactory_rate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\rate\RateBotDetector;

/**
 * Returns responses for Rate routes.
 */
class RateVote {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Votingapi result manager.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $resultManager;

  /**
   * Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Database connection object.
   *
   * @var \Drupal\rate\RateBotDetector
   */
  protected $botDetector;

  /**
   * Account proxy (the current user).
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor for vote service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\votingapi\VoteResultFunctionManager $result_manager
   *   The vote result manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The entity type manager.
   * @param \Drupal\rate\RateBotDetector $bot_detector
   *   The bot detector service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              VoteResultFunctionManager $result_manager,
                              Connection $database,
                              RateBotDetector $bot_detector,
                              AccountProxyInterface $account_proxy,
                              MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resultManager = $result_manager;
    $this->database = $database;
    $this->botDetector = $bot_detector;
    $this->accountProxy = $account_proxy;
    $this->messenger = $messenger;
  }

  /**
   * Record a vote.
   *
   * @param string $entity_type_id
   *   Entity type ID such as node.
   * @param int $entity_id
   *   Entity id of the entity type.
   * @param string $vote_type_id
   *   Vote type id.
   * @param int $value
   *   The vote value.
   * @param bool $show_messages
   *   If TRUE, standard Drupal message will be set.
   */
  public function vote($entity_type_id, $entity_id, $vote_type_id, $value, $show_messages = TRUE) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $is_bot_vote = $this->botDetector->checkIsBot();
    if (!$is_bot_vote) {
      /** @var \Drupal\votingapi\VoteStorageInterface $vote_storage */
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $vote_ids = $vote_storage->getUserVotes(
        $this->accountProxy->id(),
        $vote_type_id,
        $entity_type_id,
        $entity_id
      );

      // If user hasn't voted, save the vote.
      if (empty($vote_ids)) {
        /** @var \Drupal\votingapi\VoteTypeInterface $vote_type */
        $vote_type = $this->entityTypeManager->getStorage('vote_type')->load($vote_type_id);
        /** @var \Drupal\votingapi\VoteInterface $vote */
        $vote = $vote_storage->create(['type' => $vote_type_id]);
        $vote->setVotedEntityId($entity_id);
        $vote->setVotedEntityType($entity_type_id);
        $vote->setValueType($vote_type->getValueType());
        $vote->setValue($value);
        $vote->save();
        $this->resultManager->recalculateResults($entity_type_id, $entity_id, $vote_type_id);

        return ['status' => TRUE, 'message' => 'Your vote was added.'];
      }
      // Otherwise, inform user of previous vote.
      else
        return ['status' => FALSE, 'message' => 'You are not allowed to vote multiple times.'];
    }
  }

  /**
   * Undo a vote.
   *
   * @param string $entity_type_id
   *   Entity type ID such as node.
   * @param int $entity_id
   *   Entity id of the entity type.
   * @param bool $show_messages
   *   If TRUE, standard Drupal message will be set.
   */
  public function undoVote($entity_type_id, $entity_id, $show_messages = TRUE) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $is_bot_vote = $this->botDetector->checkIsBot();

    if (!$is_bot_vote) {
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $vote_result = $vote_storage->getUserVotes(
        $this->accountProxy->id(),
        NULL,
        $entity_type_id,
        $entity_id
      );

      // If a vote has been found, remove it.
      if (!empty($vote_result)) {
        $vote_ids = array_keys($vote_result);
        $vote_id = array_pop($vote_ids);
        $vote = $vote_storage->load($vote_id);
        if ($vote) {
          $vote->delete();
        }

        return ['status' => TRUE, 'message' => 'Your vote was canceled.'];
      }
      else
        // Otherwise, inform user of previous vote.
        return ['status' => FALSE, 'message' => 'A previous vote was not found.'];
    }
  }

}
