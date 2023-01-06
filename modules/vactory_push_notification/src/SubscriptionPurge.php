<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\vactory_push_notification\Lib\MessageSentReport;
use Psr\Log\LoggerInterface;

/**
 * This service deletes subscriptions that 'rejected' during push send.
 */
class SubscriptionPurge {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * SubscriptionPurge constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityType
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
    EntityTypeManagerInterface $entityType,
    LoggerInterface $logger
  ) {
    $this->entityStorage = $entityType->getStorage('vactory_wpn_subscription');
    $this->logger = $logger;
  }

  /**
   * Deletes subscriptions whose notification response status isn't success.
   *
   * @param \Minishlink\WebPush\MessageSentReport $report
   *   The notification statuses list.
   *
   * @see \Minishlink\WebPush\WebPush::flush()
   */
  public function delete(MessageSentReport $report) {
    if (!$report->isSuccess()) {
      $this->deleteSubscription($report->getEndpoint());
    }
  }

  /**
   * Deletes a subscription entity.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   */
  protected function deleteSubscription($endpoint) {
    $ids = $this->entityStorage->getQuery()
      ->condition('endpoint', $endpoint)
      ->execute();
    if (empty($ids)) {
      return;
    }

    $entities = $this->entityStorage->loadMultiple($ids);
    if (empty($entities)) {
      return;
    }

    try {
      $this->entityStorage->delete($entities);
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
