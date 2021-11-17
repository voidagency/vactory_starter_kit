<?php

namespace Drupal\vactory_reminder\Plugin\Reminder;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\vactory_reminder\ReminderInterface;
use Drupal\vactory_reminder\SuspendCurrentItemException;
use Drupal\vactory_sms_sender\Services\VactorySmsSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a reminder implementation for sending sms.
 *
 * @Reminder(
 *   id = "sms",
 *   title = "Send sms",
 * )
 */
class SMS extends PluginBase implements ReminderInterface, ContainerFactoryPluginInterface {

  /**
   * SMS sender service.
   *
   * @var Drupal\vactory_sms_sender\Services\VactorySmsSenderService
   */
  private $smsSenderService;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VactorySmsSenderService $smsSenderService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->smsSenderService = $smsSenderService;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_sms_sender.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['extra']['message']) || !isset($data['extra']['phone'])) {
      $suffix = !isset($data['extra']['message']) && !isset($data['extra']['phone']) ? 'message and phone params ' : '';
      $suffix = !isset($data['extra']['message']) && isset($data['extra']['phone']) ? 'message param ' : $suffix;
      $suffix = !isset($data['extra']['phone']) && isset($data['extra']['message']) ? 'phone param ' : $suffix;
      throw new SuspendCurrentItemException('There was a problem sending SMS: Missing ' . $suffix . serialize($data));
    }
    try {
      $phone = $data['extra']['phone'];
      $message = $data['extra']['message'];
      $this->smsSenderService->sendSms($phone, $message, TRUE);
    }
    catch (\Exception $e) {
      throw new SuspendCurrentItemException($e);
    }
  }

}
