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
   */
  public function testSondageStaysOpenBeforeItsCloseDate(): void {
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
