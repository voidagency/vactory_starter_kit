<?php

namespace Drupal\vactory_push_notification\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "vactory_push_queue",
 *   title = @Translation("Push notification sender"),
 * )
 */
class PushQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // todo: move to contruct
    /** @var \Drupal\vactory_push_notification\PushSender $sender */
    $sender = \Drupal::service('vactory_push_notification.sender');
    $sender->send($data);
  }

}
