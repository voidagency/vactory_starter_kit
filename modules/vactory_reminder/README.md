# Vactory Reminder
Vactory reminder is a custom module allowing running tasks depending on a
given related date base on Queue API (Queue Worker plugin type) and Reminder
new plugin type.

## Queue API
Queue API enables us to handle a number of tasks at a later stage that means
we can insert a number of items in the queue and process them later. The items
can be added to the queue by passing an arbitrary data object. And we can run
Queue by manually using Drush commands or Cron. Cron is a background process
that runs at periodic intervals of time.

## So why should we use Queue Worker?

Queue Worker allows you to run queue with time limits. You can also
individually queue the tasks at one time compared to Cron that run at the
periodic interval of time. In comparison to cron, Queue is more efficient and
can handle resource-intensive tasks. The API also allows you to revert the
item back to queue if any failure occurs. Most importantly, you can run
multiple queues without interrupting other work.

The very basic concept of Queue API is FIFO (First-In-First-Out).


## Run a specific queue by name
The module add new drush command to run reminder queue.
```
drush run-reminder-queue
drush run-reminder-queue --help
```
## Reminder plugin
Each reminder plugin define the logic of the task to execute by
implementing `processItem($data)` method.
Reminder plugins classes should be located in `my_module/src/Plugin/Reminder`
directory and annotated with `@Reminder` annotation:

       <?php

       namespace Drupal\my_module\Plugin\Reminder;

       use Drupal\Core\Plugin\PluginBase;
       use Drupal\vactory_reminder\ReminderInterface;

       /**
        * Defines a reminder implementation for my module.
        *
        * @Reminder(
        *   id = "my_reminder_plugin_id",
        *   title = "My Reminder Plugin",
        * )
        */
       class MyReminderPlugin extends PluginBase implements ReminderInterface {

         /**
          * {@inheritdoc}
          */
         public function processItem($data) {
           // My reminder plugin's task logic goes here.
         }

       }

Vactory reminder is by default providing two reminder plugins which are:
* Mail (With plugin ID "mail"): For sending emails.

  Require extra data params:
  - **email**: The destination email address.
  - **subject**: The email subject.
  - **message**: The email body message.
  - **Langcode**(Optional): The language code of the email, if omitted default
    site language code will be used instead.
* SMS (With plugin ID "sms"): For sending SMS

  Require extra data params:
  - **phone**: The destination phone number.
  - **message**: The SMS body message.

## Vactory reminder settings
The module settings form is accessible under `/admin/config/vactory-reminder`
* Time limit: The max execution time limit in second, it define the duration of
  reminder queue worker drush command execution.
* Lease time: How long the processing is expected to take in seconds, defaults
  to an hour. After this lease expires, the item will be reset and another
  consumer can claim the item.
* Reminder consumers: Here you could define on each line the consumer ID and the
  associated date interval that depends on the given context related date.

  You could add one consumer by line in format **consumerId|dateIntervalString**

  **dateIntervalString Examples**:

  - **-5 minute**: Five minutes before the related date, if positif then five
  minutes after the related date
  - **-1 hour**: One hour before the related date, if positif then one hour
  after the related date
  - **-1 day**: One day before the related date, if positif then one day after
  the related date
  - **-2 month**: Two months before the related date, if positif then two
  months after the related date
  - **-2 year**: Two years before the related date, if positif then two years
  after the related date
  - **next monday**: The closest next monday of the related date
  - **last monday**: The closest previous monday of the related date
  - **-1 hour -30 minute**: One hour 30 minutes before the related date
  - **1 hour 30 minute**: One hour 30 minutes after the related date

  **Note**:You can combine these options as per your need

  **consumerId Examples**:

  Any valid machine name string to identify the reminder consumer, (it is
  recommanded to always start with the consumer module machine name).

  - vactory_academy_inscription_mail
  - vactory_academy_formation_update
  - vactory_event_create_notification

  **Examples**:

      vactory_academy_inscription_mail|1 hour
      vactory_academy_formation_update|1 hour
      vactory_event_create_notification|-2 day

## Add a reminder task to queue
The module expose a service to add new tasks to reminder queue:

    // Reminder consumer ID (This will be used to get date interval from
    // module settings), based on above example the date interval is +1 hour.
    $consumer_id = 'vactory_academy_inscription_mail';
    // Reminder plugin ID.
    $plugin_id = 'mail';
    // Extra data example with an exact date case.
    $extra_with_date_exact = [
      'date' => time(),
      'subject' => 'Reminder Example',
      'email' => 'b.khouy@void.fr',
      'message' => 'Hello Brahim, this mail Has been sent one hour after being
                    added to queue',
    ];

    // Extra data example with date depending on an entity date field.
    $extra_with_date_exact = [
      'entity_type' => 'node',
      'entity_id' => 2,
      'date_field_name' => 'field_vactory_date',
      'subject' => 'Reminder Example',
      'email' => 'b.khouy@void.fr',
      'message' => 'Hello Brahim, this mail Has been sent one hour after being
      added to queue',
    ];

    // Create a reminder (Add to reminder queue).
    $reminder_manager = Drupal::service('vactory_reminder.queue.manager');
    $reminder_manager->reminderQueuePush(
      $consumer_id,
      $plugin_id,
      $extra
    );

## Cron
The module add a new cron job which runs, by default, every 5 minutes.
The cron job process queued reminder items (execute reminder tasks).
## Vactory module dependencies
* Vactory SMS Sender (vactory_sms_sender)
