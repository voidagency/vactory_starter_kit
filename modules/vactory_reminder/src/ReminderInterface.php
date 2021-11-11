<?php

namespace Drupal\vactory_reminder;

/**
 * Defines the common interface for all Reminder classes.
 *
 * @see \Drupal\vactory_reminder\ReminderManager
 * @see \Drupal\vactory_reminder\Annotation\Reminder
 * @see plugin_api
 */
interface ReminderInterface {

  /**
   * Works on a single queue item.
   *
   * @param mixed $data
   *   The data that was passed to
   *   \Drupal\Core\Queue\QueueInterface::createItem() when the item was queued.
   *
   * @throws \Drupal\vactory_reminder\SuspendCurrentItemException
   *   Processing is skipped for later processing.
   */
  public function processItem($data);

}
