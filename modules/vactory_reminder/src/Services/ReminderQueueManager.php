<?php

namespace Drupal\vactory_reminder\Services;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\vactory_reminder\Services\Exception\ReminderConsumerIdNotFoundException;

/**
 * Reminder queue manager service.
 */
class ReminderQueueManager {

  /**
   * Reminder queue processor.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $reminderQueueProcessor;

  /**
   * Reminder plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $reminderPluginManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    QueueFactory $queueFactory,
    PluginManagerInterface $reminderPluginManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->reminderQueueProcessor = $queueFactory->get('reminder_queue_processor');
    $this->reminderPluginManager = $reminderPluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Push new reminder task to reminder tasks queue.
   */
  public function reminderQueuePush($consumerId, string $reminderPluginId, array $extra = [], string $interval = '') {
    if (empty($consumerId) && empty($interval) && !isset($extra['interval']['reminder_field_name'])) {
      throw new \InvalidArgumentException(sprintf('Either first or last argument of %s::reminderQueuePush should not be empty, received value is "%s".', static::class, $consumerId));
    }
    if (empty($reminderPluginId)) {
      throw new \InvalidArgumentException(sprintf('The second argument of %s::reminderQueuePush should not be empty, received value is "%s".', static::class, $reminderPluginId));
    }
    if (!$this->reminderPluginManager->hasDefinition($reminderPluginId)) {
      throw new PluginNotFoundException($reminderPluginId);
    }

    $relatedDate = isset($extra['date']) ? \DateTime::createFromFormat('U', $extra['date']) : NULL;
    $item_values = [
      'date' => isset($relatedDate) ? $relatedDate->format('Y-m-d H:i:s') : NULL,
      'plugin_id' => $reminderPluginId,
      'extra' => $extra,
    ];

    if (!empty($consumerId)) {
      // Get consumer related date interval from module settings.
      $config = \Drupal::config('vactory_reminder.settings');
      $reminder_consumers = $config->get('reminder_consumers');
      if (isset($reminder_consumers) && !isset($reminder_consumers[$consumerId])) {
        throw new ReminderConsumerIdNotFoundException('Reminder Consumer ID "' . $consumerId . '" not found');
      }
      $consumers_intervals = !is_array($reminder_consumers[$consumerId]) ? [$reminder_consumers[$consumerId]] : $reminder_consumers[$consumerId];
      foreach ($consumers_intervals as $consumers_interval) {
        $item_values['interval'] = $consumers_interval;
        $this->reminderQueueProcessor->createItem($item_values);
      }
    }

    if (!empty($interval)) {
      $item_values['interval'] = $interval;
      $this->reminderQueueProcessor->createItem($item_values);
    }

    if (isset($extra['interval']['reminder_field_name'])) {
      if (!isset($extra['interval']['entity_type']) || !isset($extra['interval']['entity_id'])) {
        throw new \LogicException(__METHOD__ . ': No entity_type and/or entity_id has been specified for given interval');
      }
      $entity_type = $extra['interval']['entity_type'];
      $entity_id = $extra['interval']['entity_id'];
      $reminder_field_name = $extra['interval']['reminder_field_name'];
      $entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);
      if ($entity) {
        $interval_values = $entity->get($reminder_field_name)->getValue();
        if (!empty($interval_values)) {
          foreach ($interval_values as $interval_value) {
            $item_values['interval'] = $interval_value['value'];
            $this->reminderQueueProcessor->createItem($item_values);
          }
        }
      }
    }
  }

}
