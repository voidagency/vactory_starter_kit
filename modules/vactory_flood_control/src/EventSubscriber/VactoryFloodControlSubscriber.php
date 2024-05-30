<?php

namespace Drupal\vactory_flood_control\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\Event\UserEvents;
use Drupal\user\Event\UserFloodEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to flood events.
 */
class VactoryFloodControlSubscriber implements EventSubscriberInterface {

  /**
   * Mail manager service.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Site config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $vactoryFloodControlConfig;

  /**
   * Constructor.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerChannelFactory $logger, ConfigFactoryInterface $configFactory,) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
    $this->vactoryFloodControlConfig = $configFactory->get('vactory_flood_control.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[UserEvents::FLOOD_BLOCKED_USER][] = ['onUserBlock'];
    $events[UserEvents::FLOOD_BLOCKED_IP][] = ['onIpBlock'];
    return $events;
  }

  /**
   * Notify when user blocked.
   */
  public function onUserBlock(UserFloodEvent $floodEvent) {
    $user_id = $floodEvent->getUid();
    $message = "Flood control blocked login attempt for uid {$user_id}";
    if ($floodEvent->hasIp()) {
      $ip = $floodEvent->getIp();
      $message .= " from {$ip}";
    }
    $emails = $this->vactoryFloodControlConfig->get('emails');
    $this->sendFloodNotificationByMail('Flood control (User blocked)', $emails, $message);
    $this->logger->get('vactory_flood_control')->info($message);
  }

  /**
   * Notify when IP blocked.
   */
  public function onIpBlock(UserFloodEvent $floodEvent) {
    $emails = $this->vactoryFloodControlConfig->get('emails');
    $message = 'Flood control blocked login attempt from ' . $floodEvent->getIp();
    $this->sendFloodNotificationByMail('Flood control (User blocked)', $emails, $message);
    $this->logger->get('vactory_flood_control')->info($message);
  }

  /**
   * Send mail.
   */
  private function sendFloodNotificationByMail($subject, $to_mail, $mail_body = '') {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    // Mail.
    $module = 'vactory_flood_control';
    $key = 'vactory_flood_control_mail';
    $to = $to_mail;
    $reply = FALSE;
    $send = TRUE;
    $params['message'] = $mail_body;
    $params['subject'] = $subject;
    $params['options']['title'] = $subject;

    try {
      $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_flood_control')
        ->error("Vactory Flood Control Mail Error : " . $e->getMessage());
    }
    return FALSE;
  }

}
