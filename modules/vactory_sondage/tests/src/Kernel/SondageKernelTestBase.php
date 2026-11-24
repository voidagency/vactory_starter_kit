<?php

namespace Drupal\Tests\vactory_sondage\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\LoggerInterface;

/**
 * Creates the sondage block type with its status and close date fields.
 *
 * The block type and the two fields come from the config files of the
 * module, the other fields of the block type are not needed here.
 */
abstract class SondageKernelTestBase extends KernelTestBase implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * Name of the queue of vactory_reminder.
   */
  protected const QUEUE = 'reminder_queue_processor';

  /**
   * The shipped status field config does not pass the strict schema check.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Messages logged in the channel of vactory_sondage.
   *
   * @var string[]
   */
  protected $logged = [];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // A service of vactory_decoupled, argument of the sondage manager, which
    // is not used here: vactory_decoupled is not installed.
    $container->register('vacory_decoupled.media_file_manager', \stdClass::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installSchema('system', ['sequences']);
    $directory = $this->container->get('extension.list.module')->getPath('vactory_sondage') . '/config/install/';
    $read = fn(string $name): array => Yaml::decode(file_get_contents($directory . $name . '.yml'));
    // Created as from a config import: the storage converts the values.
    $create = fn(string $type, string $name) => $this->container->get('entity_type.manager')
      ->getStorage($type)
      ->createFromStorageRecord($read($name))
      ->save();
    $create('block_content_type', 'block_content.type.vactory_sondage');
    foreach (['field_sondage_status', 'field_sondage_close_date'] as $field) {
      $create('field_storage_config', 'field.storage.block_content.' . $field);
      $create('field_config', 'field.field.block_content.vactory_sondage.' . $field);
    }
    $this->container->get('logger.factory')->addLogger($this);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    if (($context['channel'] ?? '') === 'vactory_sondage') {
      $this->logged[] = strtr((string) $message, array_filter($context, 'is_scalar'));
    }
  }

  /**
   * Creates and saves a sondage block.
   *
   * @param int|null $close_offset
   *   Seconds from now to the close date, NULL for no close date.
   * @param string $status
   *   Status of the sondage: '1' open, '0' closed.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   The saved block.
   */
  protected function createSondage(?int $close_offset, string $status = '1'): BlockContent {
    $values = [
      'type' => 'vactory_sondage',
      'info' => 'Sondage',
      'field_sondage_status' => $status,
    ];
    if ($close_offset !== NULL) {
      $values['field_sondage_close_date'] = $this->closeDate($close_offset);
    }
    $sondage = BlockContent::create($values);
    $sondage->save();
    return $sondage;
  }

  /**
   * Returns a close date, as stored by the datetime field.
   *
   * @param int $offset
   *   Seconds from now.
   *
   * @return string
   *   The date in UTC.
   */
  protected function closeDate(int $offset): string {
    return gmdate('Y-m-d\TH:i:s', time() + $offset);
  }

  /**
   * Returns the data of the items of the reminder queue.
   *
   * @return array[]
   *   Item data, in creation order.
   */
  protected function queuedItems(): array {
    $database = $this->container->get('database');
    // The queue table is created with the first item.
    if (!$database->schema()->tableExists('queue')) {
      return [];
    }
    $rows = $database->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('name', self::QUEUE)
      ->orderBy('item_id')
      ->execute()
      ->fetchCol();
    return array_map('unserialize', $rows);
  }

}
