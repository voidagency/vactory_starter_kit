<?php

namespace Drupal\vactory_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Url;
use Drupal\vactory_push_notification\KeysHelper;
use Drupal\vactory_push_notification\NotificationItem;
use Drupal\vactory_push_notification\NotificationQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows to send a test notification to subscribed users.
 */
class TestNotification extends FormBase {

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * @var \Drupal\vactory_push_notification\NotificationQueue
   */
  protected $queue;

  /**
   * The vactory_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManger;

  /**
   * Constructs a new TestNotification object.
   *
   * @param \Drupal\vactory_push_notification\KeysHelper $keys_helper
   *   The keys helper service.
   * @param \Drupal\vactory_push_notification\NotificationQueue $queue
   *   The notification queue service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   The queue worker manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
      KeysHelper $keys_helper,
      NotificationQueue $queue,
      ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type,
      QueueWorkerManagerInterface $queue_worker_manager
  ) {
    $this->keysHelper = $keys_helper;
    $this->queue = $queue;
    $this->config = $config_factory->get('vactory_push_notification.settings');
    $this->storage = $entity_type->getStorage('vactory_wpn_subscription');
    $this->queueWorkerManger = $queue_worker_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_push_notification.keys_helper'),
      $container->get('vactory_push_notification.queue'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_push_notification_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = $this->storage->getQuery()->count()->execute();
    if ($count == 0) {
      $this->messenger()->addWarning($this->t('No subscriptions found.'));
      return $form;
    }

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test notification'),
    ];
    $form['test']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 128,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
      '#default_value' => 'Hello notification'
    ];
    $form['test']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => 'Body notification',
      '#description' => $this->t('Keep in mind that your message will be trimmed to <strong>%chars</strong> characters. You can adjust that value on <a href=":url">Settings</a> page.', [
        '%chars' => $this->config->get('body_length') ?: 100,
        ':url' => Url::fromRoute('vactory_push_notification.settings')->toString(),
      ]),
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['test']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Enter the icon URL which will show in the notification.'),
      '#maxlength' => 512,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['test']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Enter the URL on which user will redirect after clicking on the notification.'),
      '#maxlength' => 512,
      '#size' => 64,
      '#weight' => '0',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#button_type' => 'primary',
    ];

    // $form['actions']['run'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Run Queue'),
    //   '#limit_validation_errors' => [],
    //   '#submit' => ['::runQueue'],
    // ];

    return $form;
  }

  public function runQueue(array &$form, FormStateInterface $form_state) {
    $queue = \Drupal::queue('vactory_push_queue');

    /** @var \Drupal\vactory_push_notification\Plugin\QueueWorker\PushQueueWorker $worker */
    $worker = \Drupal::service('plugin.manager.queue_worker')
      ->createInstance('vactory_push_queue');

    // Process queue items during only specified amount of time.
    $finish = strtotime('+ 5 min');
    while (time() < $finish && ($item = $queue->claimItem())) {
      try {
        $worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (\Exception $e) {
        watchdog_exception('vactory_push_notification', $e, $e->getMessage());
      }
    }
    $this->messenger()->addStatus($this->t('Queue Run.'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $item = new NotificationItem();
    $item->title = $form_state->getValue('title');
    $item->body = $form_state->getValue('body');
    $item->icon = $form_state->getValue('icon');
    $item->url = $form_state->getValue('url');

    // TODO: make a batch process.

    // $this->queue->startWithItem($item);
    $queue = $this->queue->getQueue();
    $queue->createItem($item);
    $this->queue->startWithItem($item);

    // $queue = \Drupal::queue('vactory_push_queue');

    /** @var \Drupal\vactory_push_notification\Plugin\QueueWorker\PushQueueWorker $worker */
    $worker = \Drupal::service('plugin.manager.queue_worker')
      ->createInstance('vactory_push_queue');

      //startWithItem

    // Process queue items during only specified amount of time.
    $finish = strtotime('+ 5 min');
    while (time() < $finish && ($item = $queue->claimItem())) {
      try {
        $worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (\Exception $e) {
        watchdog_exception('vactory_push_notification', $e, $e->getMessage());
      }
    }

    $this->messenger()->addStatus($this->t('Notification added to queue. Run Queue to process.'));
    // $worker = $this->queueWorkerManger->createInstance('vactory_push_queue');

    // while ($unprocessed = $queue->claimItem()) {
    //   try {
    //     $worker->processItem($unprocessed->data);
    //     $queue->deleteItem($unprocessed);
    //   }
    //   catch (SuspendQueueException $e) {
    //     $queue->releaseItem($item);
    //   }
    //   catch (\Exception $e) {

    //   }
    // }
  }

}
