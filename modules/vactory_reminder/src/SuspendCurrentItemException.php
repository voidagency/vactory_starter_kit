<?php

namespace Drupal\vactory_reminder;

/**
 * Exception class to throw to indicate that a cron queue item should be Skipped in this CRON run.
 *
 * An implementation of \Drupal\Core\Queue\QueueWorkerInterface::processItem()
 * throws this class of exception to indicate that processing of the queue item
 * should be skipped. This should be thrown rather than a normal Exception if
 * the problem encountered by the queue worker is such that it can be deduced
 * that workers of subsequent items would not necessarily encounter it too. For
 * example, if each worker is waiting for a unique/specific remote job to be
 * complete.
 */
class SuspendCurrentItemException extends \RuntimeException {}
