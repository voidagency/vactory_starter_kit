<?php

namespace Drupal\vactory_flood_control\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\user\Entity\User;
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
   * Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Emails.
   *
   * @var string
   */
  private mixed $vactoryFloodControlConfigEmails;

  /**
   * Constructor.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerChannelFactory $logger, ConfigFactoryInterface $configFactory, Token $token) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
    if ($language_manager->getCurrentLanguage()->isDefault()) {
      $this->vactoryFloodControlConfig = $configFactory->get('vactory_flood_control.settings');
    }
    else {
      $langcode = $language_manager->getCurrentLanguage()->getId();
      $this->vactoryFloodControlConfig = $language_manager->getLanguageConfigOverride($langcode, 'vactory_flood_control.settings');
    }
    $this->vactoryFloodControlConfigEmails = $configFactory->get('vactory_flood_control.settings')->get('emails');
    $this->token = $token;
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
    $user_ip = '';
    if ($floodEvent->hasIp()) {
      $user_ip = $floodEvent->getIp();
    }
    $subject = $this->vactoryFloodControlConfig->get('user_flood_notification.blocked_user.user_flood_notification_subject');
    $message = $this->vactoryFloodControlConfig->get('user_flood_notification.blocked_user.user_flood_notification_body');
    $user = User::load($user_id);
    $tokens_data = [
      'vactory_flood_control_user' => $user,
      'vactory_flood_control_ip' => $user_ip,
    ];
    $message = $this->token->replace($message, $tokens_data);
    $subject = $this->token->replace($subject, $tokens_data);

    $this->sendFloodNotificationByMail($subject, $this->vactoryFloodControlConfigEmails, $message);
    $this->logger->get('vactory_flood_control')->info($message);
  }

  /**
   * Notify when IP blocked.
   */
  public function onIpBlock(UserFloodEvent $floodEvent) {
    $subject = $this->vactoryFloodControlConfig->get('user_flood_notification.blocked_ip.user_flood_notification_subject_ip');
    $message = $this->vactoryFloodControlConfig->get('user_flood_notification.blocked_ip.user_flood_notification_body_ip');
    $tokens_data = [
      'vactory_flood_control_ip' => $floodEvent->getIp(),
    ];
    $subject = $this->token->replace($subject, $tokens_data);
    $message = $this->token->replace($message, $tokens_data);
    $this->sendFloodNotificationByMail($subject, $this->vactoryFloodControlConfigEmails, $message);
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
