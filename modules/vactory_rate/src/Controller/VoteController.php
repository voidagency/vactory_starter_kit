<?php

namespace Drupal\vactory_rate\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\vactory_rate\RateVote;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\votingapi\VoteResultFunctionManager;

/**
 * Returns responses for Rate routes.
 */
class VoteController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory wrapper to fetch settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The vote service.
   *
   * @var \Drupal\vactory_rate\RateVote
   */
  protected $rateVote;

  /**
   * Votingapi result manager.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $resultManager;

  /**
   * Constructs a Vote Controller.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\vactory_rate\RateVote $rate_vote
   *   The bot detector service.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              EntityTypeManagerInterface $entity_type_manager,
                              CacheTagsInvalidatorInterface $cache_tags_invalidator,
                              RendererInterface $renderer,
                              RateVote $rate_vote,
                              VoteResultFunctionManager $resultManager) {
    $this->config = $config_factory->get('rate.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->renderer = $renderer;
    $this->rateVote = $rate_vote;
    $this->resultManager = $resultManager;                            
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('renderer'),
      $container->get('vactory_rate.vote'),
      $container->get('plugin.manager.votingapi.resultfunction')
    );
  }

  /**
   * Invalidate cache tags to update vote display.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $bundle
   *   The bundle name.
   */
  protected function invalidateCacheTags($entity_type_id, $entity_id, $bundle) {
    $invalidate_tags = [
      $entity_type_id . ':' . $entity_id,
      'vote:' . $bundle . ':' . $entity_id,
    ];
    $this->cacheTagsInvalidator->invalidateTags($invalidate_tags);
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
   *   The vote.
   * @param string $widget_type
   *   Widget type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   */
  public function vote($entity_type_id, $entity_id, $vote_type_id, $value) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $voteStatus = $this->rateVote->vote($entity_type_id, $entity_id, $vote_type_id, $value, false);
    $this->invalidateCacheTags($entity_type_id, $entity_id, $entity->bundle());
    $voteResults = $this->resultManager->getResults($entity_type_id, $entity_id);
    $obj_merged = (object) array_merge((array) $voteStatus, (array) $voteResults);
    return new JsonResponse($obj_merged);
  }

  /**
   * Undo a vote.
   *
   * @param string $entity_type_id
   *   Entity type ID such as node.
   * @param int $entity_id
   *   Entity id of the entity type.
   * @param string $widget_type
   *   Widget type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   */
  public function undoVote($entity_type_id, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $voteStat = $this->rateVote->undoVote($entity_type_id, $entity_id, false);
    $this->invalidateCacheTags($entity_type_id, $entity_id, $entity->bundle());
    $voteRes = $this->resultManager->getResults($entity_type_id, $entity_id);
    $obj_merged = (object) array_merge((array) $voteStat, (array) $voteRes);
    return new JsonResponse($obj_merged);
  }

  /**
   * Display voting results.
   *
   * @param string $entity_type_id
   *   Entity type ID such as node.
   * @param int $entity_id
   *   Entity id of the entity type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response object.
   */
  public function results($entity_type_id, $entity_id) {
    $result = $this->resultManager->getResults($entity_type_id, $entity_id);
    $current_user = $this->currentUser()->id();
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    $vote_ids = $vote_storage->getUserVotes(
      $current_user,
      'fivestar',
      $entity_type_id,
      $entity_id
    );
    if (!empty($vote_ids)) {
      foreach ($vote_ids as $vote_id) {
        if($vote_storage->load($vote_id)->getVotedEntityId() === $entity_id)
          $vote = $vote_storage->load($vote_id)->getValue();
      }
    }
    $hasVoted = empty($vote_ids) ? false : true;
    if(empty($result))
      return new JSONResponse(['status' => FALSE, 'message' => 'No votes found', 'user' => $current_user, 'hasVoted' => $hasVoted]);
    $obj = (object) array_merge((array) ['status' => TRUE], (array) $result, ['user' => $current_user, 'hasVoted' => $hasVoted, 'vote' => $vote]);
    return new JsonResponse($obj);
  }

}