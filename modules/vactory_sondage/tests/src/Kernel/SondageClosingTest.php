<?php

namespace Drupal\Tests\vactory_sondage\Kernel;

use Drupal\block_content\Entity\BlockContent;

/**
 * Tests the closing of a sondage at its close date (Redmine #71443).
 *
 * A new sondage queued a reminder item without its ID: the ID does not exist
 * yet when the block is presaved. The reminder queue failed on that item at
 * every run and the sondage was never closed.
 *
 * @group vactory_sondage
 */
class SondageClosingTest extends SondageKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'block',
    'block_content',
    'vactory_reminder',
    'vactory_sondage',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Registers the consumer of the module in the reminder settings.
    $this->container->get('module_handler')->loadInclude('vactory_sondage', 'install');
    vactory_sondage_install(FALSE);
  }

  /**
   * A sondage whose close date has passed is closed by the reminder queue.
   */
  public function testSondageIsClosedAfterItsCloseDate(): void {
    $sondage = $this->createSondage(-3600);
    $items = $this->queuedItems();
    $this->assertCount(1, $items);
    $this->assertSame('sondage_closer', $items[0]['plugin_id']);
    $this->assertSame('block_content', $items[0]['extra']['entity_type']);
    $this->assertEquals($sondage->id(), $items[0]['extra']['entity_id']);

    $this->processReminderQueue();

    $this->assertSame([], $this->logged);
    $this->assertSame('0', BlockContent::load($sondage->id())->get('field_sondage_status')->value);
  }

  /**
   * A sondage whose close date has not come yet stays open.
   *
   * The reminder queue reads the stored UTC date in the timezone of the site.
   * On a site ahead of UTC, it handed the item over before the close date.
   */
  public function testSondageStaysOpenBeforeItsCloseDate(): void {
    date_default_timezone_set('Asia/Tokyo');
    $sondage = $this->createSondage(3600);

    $this->processReminderQueue();

    $this->assertSame('1', BlockContent::load($sondage->id())->get('field_sondage_status')->value);
    // The item is kept for a later run.
    $this->assertCount(1, $this->queuedItems());
  }

  /**
   * A close date is scheduled when it is set or changed, not otherwise.
   */
  public function testScheduledWhenTheCloseDateIsSetOrChanged(): void {
    $sondage = $this->createSondage(NULL);
    $this->assertCount(0, $this->queuedItems(), 'Without close date, nothing to schedule.');

    $sondage->set('info', 'Sondage renommé')->save();
    $this->assertCount(0, $this->queuedItems());

    $sondage->set('field_sondage_close_date', $this->closeDate(3600))->save();
    $this->assertCount(1, $this->queuedItems());

    $sondage->set('info', 'Sondage renommé deux fois')->save();
    $this->assertCount(1, $this->queuedItems(), 'Same close date: not scheduled again.');

    $sondage->set('field_sondage_close_date', $this->closeDate(7200))->save();
    $this->assertCount(2, $this->queuedItems());
  }

  /**
   * A closing whose sondage was deleted or lost its date is dropped.
   *
   * The reminder queue loaded the sondage to read its date: without it, the
   * item failed at every run, with an error logged each time.
   */
  public function testClosingOfGoneSondageOrDateIsDropped(): void {
    $deleted = $this->createSondage(-3600);
    $cleared = $this->createSondage(-3600);
    $deleted->delete();
    $cleared->set('field_sondage_close_date', NULL)->save();

    $this->processReminderQueue();

    $this->assertSame([], $this->logged);
    $this->assertCount(0, $this->queuedItems());
    $this->assertSame('1', BlockContent::load($cleared->id())->get('field_sondage_status')->value);
  }

  /**
   * A closing queued for a close date that was changed since is dropped.
   */
  public function testClosingOfChangedDateIsDropped(): void {
    $sondage = $this->createSondage(-3600);
    $sondage->set('field_sondage_close_date', $this->closeDate(3600))->save();

    $this->processReminderQueue();

    $this->assertSame('1', BlockContent::load($sondage->id())->get('field_sondage_status')->value);
    // Only the closing at the new date is left.
    $items = $this->queuedItems();
    $this->assertCount(1, $items);
    $this->assertSame($this->closeDate(3600), $items[0]['extra']['close_date']);
  }

  /**
   * On a site behind UTC, a sondage is closed once its close date passed.
   *
   * The reminder queue read the stored UTC date in the timezone of the site:
   * on a site at UTC-5, the closing came five hours late.
   */
  public function testSondageIsClosedOnTimeOnSiteBehindUtc(): void {
    $this->config('system.date')->set('timezone.default', 'America/New_York')->save();
    date_default_timezone_set('America/New_York');
    $sondage = $this->createSondage(-600);

    $this->processReminderQueue();

    $this->assertSame([], $this->logged);
    $this->assertSame('0', BlockContent::load($sondage->id())->get('field_sondage_status')->value);
  }

  /**
   * The update repairs the closing items queued without a sondage ID.
   */
  public function testUpdateRepairsTheQueuedClosings(): void {
    $future = $this->createSondage(3600);
    $this->createSondage(-3600);
    $this->createSondage(3600, '0');
    $this->createSondage(NULL);
    $this->container->get('database')->delete('queue')->execute();
    // Items as queued by the previous code: no sondage ID.
    $queue = $this->container->get('queue')->get(self::QUEUE);
    $broken = [
      'date' => NULL,
      'plugin_id' => 'sondage_closer',
      'extra' => [
        'entity_type' => 'block_content',
        'entity_id' => NULL,
        'date_field_name' => 'field_sondage_close_date',
      ],
      'interval' => '1 min',
    ];
    $queue->createItem($broken);
    $queue->createItem($broken);
    // An item of another reminder plugin is kept.
    $other = ['plugin_id' => 'mail', 'interval' => '1 day', 'date' => NULL];
    $queue->createItem($other);

    $this->container->get('module_handler')->loadInclude('vactory_sondage', 'install');
    $message = (string) vactory_sondage_update_10001();

    $items = $this->queuedItems();
    $this->assertCount(2, $items);
    $this->assertSame($other, $items[0]);
    $this->assertEquals($future->id(), $items[1]['extra']['entity_id']);
    $this->assertStringContainsString('2 broken items deleted, 1 open sondages scheduled, 1 open sondages with a passed close date', $message);
  }

  /**
   * The update runs on a site where no queue table was ever created.
   *
   * Core creates the queue table with the first queued item. "drush updb"
   * runs updates in a batch, which creates it: the update is also called
   * outside of a batch here.
   */
  public function testUpdateWithoutQueueTable(): void {
    $future = $this->createSondage(3600);
    $this->container->get('database')->schema()->dropTable('queue');

    $this->container->get('module_handler')->loadInclude('vactory_sondage', 'install');
    $message = (string) vactory_sondage_update_10001();

    $this->assertStringContainsString('0 broken items deleted, 1 open sondages scheduled', $message);
    $items = $this->queuedItems();
    $this->assertCount(1, $items);
    $this->assertEquals($future->id(), $items[0]['extra']['entity_id']);
  }

  /**
   * Processes the items of the reminder queue, as "drush rrq" does.
   */
  protected function processReminderQueue(): void {
    $queue = $this->container->get('queue')->get(self::QUEUE);
    $worker = $this->container->get('plugin.manager.queue_worker')->createInstance(self::QUEUE);
    $claimed = [];
    while ($item = $queue->claimItem()) {
      $claimed[] = $item;
      try {
        $worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (\Exception $e) {
        $this->logged[] = get_class($e) . ': ' . $e->getMessage();
      }
    }
    // Items left in the queue are claimed again by a later run.
    foreach ($claimed as $item) {
      $queue->releaseItem($item);
    }
  }

}
