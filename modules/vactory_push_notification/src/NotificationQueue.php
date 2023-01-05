<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Creates a queue for notification send.
 */
class NotificationQueue {

  /**
   * The vactory_push_queue queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The vactory_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SendMessage constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    QueueFactory $queueFactory,
    EntityTypeManagerInterface $entityManager,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->queue = $queueFactory->get('vactory_push_queue');
    $this->entityManager = $entityManager;
    $this->config = $config_factory->get('vactory_push_notification.settings');
    $this->moduleHandler = $module_handler;
  }

  /**
   * Returns a notification queue.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The notification queue.
   */
  public function getQueue() {
    return $this->queue;
  }

  /**
   * Starts a send queue with an content entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function start(NodeInterface $entity) {
    $bundle = $entity->bundle();
    $fields = $this->config->get("fields.$bundle");

    $item = new NotificationItem();
    $item->title = $entity->getTitle();
    $item->bundle = $bundle;

    // Prepare a notification info.
    if (isset($fields['body'])) {
      $item->body = $entity->get($fields['body'])->value;
    }

    // Prepare a notification icon.
    if (isset($fields['icon'])) {
      $item->icon = $this->getIconUrl($entity->get($fields['icon']));
    }

    // Prepare a notification url.
    $item->url = $entity->toUrl('canonical', [
      'absolute' => TRUE,
    ])->toString();

    $this->startWithItem($item);
  }

  /**
   * Starts a send queue with a notification item.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $baseItem
   *   The notification item.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function startWithItem(NotificationItem $baseItem) {
    $query = $this->entityManager
      ->getStorage('vactory_wpn_subscription')
      ->getQuery();

    $start = 0;
    $limit = $this->config->get('queue_batch_size');
    $full_body = $baseItem->body;
    $trimmed_body = $this->prepareBody($full_body);

    while ($ids = $query->range($start, $limit)->execute()) {
      $item = clone $baseItem;
      $item->ids = $ids;
      $item->body = $trimmed_body;
      $this->moduleHandler->alter('vactory_push_notification_item', $item, $full_body);
      $this->queue->createItem($item);
      $start += $limit;
    }
  }

  /**
   * Returns an icon url from the entity field.
   *
   * @param \Drupal\Core\Field\FieldItemList $field
   *   The entity field.
   *
   * @return string
   *   An icon url.
   */
  protected function getIconUrl($field) {
    if ($field instanceof FileFieldItemList) {
      if (!($entities = $field->referencedEntities())) {
        return '';
      }
      $file = reset($entities);
      return $file->toUrl()->toString();
    }
    return '';
  }

  /**
   * Prepares a notification body: trim and strip html tags.
   *
   * @param string $raw
   *   The raw text.
   *
   * @return string
   *   A trimmed and filtered text.
   */
  protected function prepareBody($raw) {
    $body = trim(strip_tags($raw));
    if (empty($body)) {
      return '';
    }

    $body = FieldPluginBase::trimText([
      'max_length'    => $this->config->get('body_length') ?: 100,
      'word_boundary' => TRUE,
      'ellipsis'      => TRUE,
      'html'          => FALSE,
    ], $body);

    return $body;
  }

}
