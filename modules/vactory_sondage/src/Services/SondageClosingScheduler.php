<?php

namespace Drupal\vactory_sondage\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Schedules the closing of a sondage at its close date.
 *
 * The closing is an item of the reminder queue of vactory_reminder, processed
 * by the "sondage_closer" reminder plugin once the close date has passed.
 */
class SondageClosingScheduler {

  /**
   * Reminder consumer of the module, set by vactory_sondage_install().
   */
  public const CONSUMER_ID = 'vactory_sondage_closer';

  /**
   * Reminder plugin that closes a sondage.
   */
  public const PLUGIN_ID = 'sondage_closer';

  /**
   * Field holding the close date of a sondage.
   */
  public const DATE_FIELD = 'field_sondage_close_date';

  /**
   * Queue of vactory_reminder.
   */
  public const QUEUE = 'reminder_queue_processor';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The reminder queue manager of vactory_reminder, NULL without it.
   *
   * @var \Drupal\vactory_reminder\Services\ReminderQueueManager|null
   */
  protected $reminderQueueManager;

  /**
   * Constructs the scheduler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\vactory_reminder\Services\ReminderQueueManager|null $reminder_queue_manager
   *   The reminder queue manager of vactory_reminder, NULL without it.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, LoggerChannelFactoryInterface $logger_factory, $reminder_queue_manager = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    $this->reminderQueueManager = $reminder_queue_manager;
  }

  /**
   * Schedules the closing of a saved sondage when its close date is new.
   *
   * A queued item reads the close date when it is processed: a sondage is
   * scheduled again only when its close date is set or changed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity just inserted or updated.
   */
  public function onSave(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() !== 'block_content' || $entity->bundle() !== 'vactory_sondage') {
      return;
    }
    $date = $entity->get(self::DATE_FIELD)->value;
    $original = isset($entity->original) ? $entity->original->get(self::DATE_FIELD)->value : NULL;
    if (!empty($date) && $date !== $original) {
      $this->schedule($entity);
    }
  }

  /**
   * Queues the closing of a sondage.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sondage
   *   A saved sondage block.
   */
  public function schedule(EntityInterface $sondage): void {
    if (!$this->reminderQueueManager) {
      $this->loggerFactory->get('vactory_sondage')->warning('Sondage @id is not closed at its close date: vactory_reminder is not enabled.', [
        '@id' => $sondage->id(),
      ]);
      return;
    }
    $this->reminderQueueManager->reminderQueuePush(self::CONSUMER_ID, self::PLUGIN_ID, [
      'entity_type' => $sondage->getEntityTypeId(),
      'entity_id' => $sondage->id(),
      'date_field_name' => self::DATE_FIELD,
    ]);
  }

  /**
   * Repairs the closing items queued before Redmine #71443.
   *
   * Those items have no sondage ID: they fail at every run of the reminder
   * queue. They are deleted, and the closing of every open sondage whose
   * close date is still to come is queued again. An open sondage whose close
   * date has passed is left open.
   *
   * @return int[]
   *   Counts, keyed by "removed", "scheduled" and "past" (open sondages
   *   whose close date has passed).
   */
  public function repairQueue(): array {
    $removed = 0;
    $rows = $this->database->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('name', self::QUEUE)
      ->execute();
    foreach ($rows as $row) {
      $data = unserialize($row->data, ['allowed_classes' => FALSE]);
      if (is_array($data) && ($data['plugin_id'] ?? NULL) === self::PLUGIN_ID && empty($data['extra']['entity_id'])) {
        $this->database->delete('queue')->condition('item_id', $row->item_id)->execute();
        $removed++;
      }
    }

    $now = gmdate('Y-m-d\TH:i:s');
    $storage = $this->entityTypeManager->getStorage('block_content');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'vactory_sondage')
      ->condition('field_sondage_status', '1')
      ->exists(self::DATE_FIELD)
      ->execute();
    $counts = ['removed' => $removed, 'scheduled' => 0, 'past' => 0];
    foreach ($storage->loadMultiple($ids) as $sondage) {
      if ($sondage->get(self::DATE_FIELD)->value > $now) {
        $this->schedule($sondage);
        $counts['scheduled']++;
        continue;
      }
      $counts['past']++;
    }
    return $counts;
  }

}
