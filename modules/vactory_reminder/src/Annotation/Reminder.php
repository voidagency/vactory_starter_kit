<?php

namespace Drupal\vactory_reminder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a reminder annotation object.
 *
 * Plugin Namespace: Plugin\Reminder.
 *
 * For a working example, see \Drupal\vactory_reminder\Plugin\Reminder\Mail
 *
 * @see \Drupal\vactory_reminder\ReminderManager
 * @see \Drupal\vactory_reminder\ReminderInterface
 * @see plugin_api
 * @see hook_reminder_info_alter()
 *
 * @Annotation
 */
class Reminder extends Plugin {

  /**
   * The reminder plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the reminder plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
