<?php

namespace Drupal\vactory_notifications\Services;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\vactory_notifications\Event\VactoryNotificationsToastEvent;

/**
 * Class VactoryNotificationsService.
 */
class VactoryNotificationsService {

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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Notification config.
   *
   * @var array
   */
  protected $notificationConfig;

  /**
   * Event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * @param MailManagerInterface $mail_manager
   *   Mail manager service
   * @param LanguageManagerInterface $language_manager
   *   Language manager service
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service
   * @param ConfigFactoryInterface $configFactory
   *   Config factory service
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    Token $token,
    ContainerAwareEventDispatcher $eventDispatcher
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->notificationConfig = $this->configFactory->get('vactory_notifications.settings');
    $this->token = $token;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Function to send mail.
   *
   * @param string $subject
   *   Subject.
   * @param string $to_mail
   *   Destination mail.
   * @param string $mail_body
   *   Data (If empty, fallback to config's default mail body).
   */

  public function sendNotificationByMail($subject, $to_mail, $mail_body = '') {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    // Mail.
    $module = 'vactory_notifications';
    $key = 'vactory_notifications_mail_body';
    $to = $to_mail;
    $reply = FALSE;
    $send = TRUE;
    $params['message'] = $mail_body;
    $params['subject'] = $subject;
    $params['options']['title'] = $subject;
    /* @var  /Drupal\Core\Mail\MailManager $mailManager */
    try {
      $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_notifications')
        ->error("Erreur lors de l'envoi de notification par mail : " . $e->getMessage());
    }
    return FALSE;
  }

  /**
   * Get concerned users IDs form module settings.
   */
  public function getNotificationsUsersIds($bundle) {
    $uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->condition('status', 1)
      ->execute();
    $users = $this->entityTypeManager->getStorage('user')
      ->loadMultiple($uids);
    $users_ids = [];
    foreach ($users as $user) {
      $user_roles = $user->getRoles();
      foreach ($user_roles as $role_name) {
        $role_content_types = $this->notificationConfig->get($role_name . '_content_types');
        if (!empty($role_content_types) && in_array($bundle, $role_content_types)) {
          $users_ids[] = $user->id();
        }
      }
    }

    return $users_ids;
  }

  public /**
   * Auto translate a notification function for enabled languages.
   */
  function notificationsAutoTranslate($notification) {
    $enabled_languages = $this->languageManager->getLanguages();
    foreach ($enabled_languages as $langcode => $language) {
      if (!$language->isDefault()) {
        $notification_config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_notifications.settings');
        $translated_notification_title = $notification_config_translation->get('notifications_default_title');
        $translated_notification_message = $notification_config_translation->get('notifications_default_message');
        $translated_mail_subject = $notification_config_translation->get('mail_default_subject');
        $translated_mail_message = $notification_config_translation->get('mail_default_message');
        $translated_notification = $notification->addTranslation($langcode);
        $translated_notification_title = isset($translated_notification_title) ? $translated_notification_title : $this->notificationConfig->get('notifications_default_title');
        $translated_notification_message = isset($translated_notification_message) ? $translated_notification_message : $this->notificationConfig->get('notifications_default_message');
        $translated_mail_subject = isset($translated_mail_subject) ? $translated_mail_subject : $this->notificationConfig->get('mail_default_subject');
        $translated_mail_message = isset($translated_mail_message) ? $translated_mail_message : $this->notificationConfig->get('mail_default_message');
        // Tokens replacement.
        $translated_notification->name = $this->token->replace($translated_notification_title, ['entity' => $notification]);
        $translated_notification->notification_message = $this->token->replace($translated_notification_message, ['entity' => $notification]);
        $translated_notification->mail_subject = $this->token->replace($translated_mail_subject, ['entity' => $notification]);
        $translated_notification->mail_message = $this->token->replace($translated_mail_message, ['entity' => $notification]);
        $translated_notification->save();
      }
    }
  }

  /**
   * Check if notifications are enabled for the given bundle.
   */
  public function isNotificationsEnabledForBundle($bundle) {
    $roles = $this->entityTypeManager->getStorage('user_role')
      ->loadMultiple();
    foreach (array_keys($roles) as $role) {
      $content_types = $this->notificationConfig->get($role . '_content_types');
      if (in_array($bundle, $content_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function triggerNotificationsToast($notification) {
    $event = new VactoryNotificationsToastEvent($notification);
    $this->eventDispatcher->dispatch(VactoryNotificationsToastEvent::EVENT_NAME, $event);
  }

}
