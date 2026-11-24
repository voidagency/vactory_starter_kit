<?php

namespace Drupal\vactory_sondage\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Schedules the closing of a sondage at its close date.
 *
 * The closing is an item of the reminder queue of vactory_reminder, processed
 * by the "sondage_closer" reminder plugin once the close date has passed.
 * The item carries its close date: the reminder queue does not load the
 * sondage, and the plugin drops an item whose sondage is gone or whose close
 * date was cleared or changed since.
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
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the scheduler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, LoggerChannelFactoryInterface $logger_factory, QueueFactory $queue_factory, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    $this->queueFactory = $queue_factory;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Schedules the closing of a saved sondage when its close date is new.
   *
   * A sondage is scheduled when its close date is set or changed. An item
   * queued for a previous date is dropped when it is processed.
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
   * The reminder queue reads the date of an item in the timezone of the site:
   * the item date is the close date in that timezone. The close date stored
   * in UTC is kept in the item, to recognize an outdated item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sondage
   *   A saved sondage block, with a close date.
   */
  public function schedule(EntityInterface $sondage): void {
    if (!$this->moduleHandler->moduleExists('vactory_reminder')) {
      $this->loggerFactory->get('vactory_sondage')->warning('Sondage @id is not closed at its close date: vactory_reminder is not enabled.', [
        '@id' => $sondage->id(),
      ]);
      return;
    }
    $close_date = $sondage->get(self::DATE_FIELD)->value;
    $site_timezone = $this->configFactory->get('system.date')->get('timezone.default') ?: date_default_timezone_get();
    $date = new \DateTime($close_date, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date->setTimezone(new \DateTimeZone($site_timezone));
    $consumers = $this->configFactory->get('vactory_reminder.settings')->get('reminder_consumers');
    $intervals = is_array($consumers) && isset($consumers[self::CONSUMER_ID]) ? (array) $consumers[self::CONSUMER_ID] : ['1 min'];
    $queue = $this->queueFactory->get(self::QUEUE);
    foreach ($intervals as $interval) {
      $queue->createItem([
        'date' => $date->format('Y-m-d H:i:s'),
        'plugin_id' => self::PLUGIN_ID,
        'extra' => [
          'entity_type' => $sondage->getEntityTypeId(),
          'entity_id' => $sondage->id(),
          'date_field_name' => self::DATE_FIELD,
          'close_date' => $close_date,
        ],
        'interval' => $interval,
      ]);
    }
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
