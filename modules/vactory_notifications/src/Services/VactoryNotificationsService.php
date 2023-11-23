<?php

namespace Drupal\vactory_notifications\Services;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
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
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;


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
    EntityRepositoryInterface $entityRepository,
    ConfigFactoryInterface $configFactory,
    Token $token,
    ContainerAwareEventDispatcher $eventDispatcher
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
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
    $users_ids = [];
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role) {
      $role_content_types = $this->notificationConfig->get($role->id() . '_content_types');
      if (!empty($role_content_types) && in_array($bundle, $role_content_types)) {
        if ($role->id() === 'authenticated') {
          $uids = \Drupal::entityQuery('user')
            ->condition('status', 1)
            ->accessCheck(FALSE)
            ->execute();
        }
        else {
          $uids = \Drupal::entityQuery('user')
            ->condition('status', 1)
            ->condition('roles', $role->id())
            ->accessCheck(FALSE)
            ->execute();
        }
        if (!empty($uids)) {
          $users_ids = array_merge($users_ids, $uids);
          $users_ids = array_unique($users_ids);
        }
      }
    }
    return array_values($users_ids);
  }

  /**
   * Auto translate a notification function for enabled languages.
   */
  public function notificationsAutoTranslate($notification) {
    $enabled_languages = $this->languageManager->getLanguages();
    $related_nid = $notification->get('notification_related_content')->target_id;
    $related_node = NULL;
    if ($related_nid) {
      $node = $this->entityTypeManager->getStorage('node')
        ->load($related_nid);
      if ($node && $node instanceof NodeInterface) {
        $related_node = $node;
      }
    }
    foreach ($enabled_languages as $langcode => $language) {
      if (!$language->isDefault()) {
        $translated_node = NULL;
        // If related node exists then get related translation.
        if ($related_node) {
          $translated_node = $this->entityRepository->getTranslationFromContext($related_node, $langcode);
        }
        $notification_config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_notifications.settings');
        $translated_notification_title = $notification_config_translation->get('notifications_default_title');
        $translated_notification_message = $notification_config_translation->get('notifications_default_message');
        $translated_mail_subject = $notification_config_translation->get('mail_default_subject');
        $translated_mail_message = $notification_config_translation->get('mail_default_message');
        if ($translated_node && $related_node->hasTranslation($langcode)) {
          // Override notification default values with node translation data.
          $node_notification_title = $translated_node->get('notification_title')->value;
          $node_notification_message = $translated_node->get('notification_message')->value;
          $node_notification_mail_sub = $translated_node->get('mail_subject')->value;
          $node_notification_mail_message = $translated_node->get('mail_message')->value;
          $translated_notification_title = !empty($node_notification_title) ? $node_notification_title : $translated_notification_title;
          $translated_notification_message = !empty($node_notification_message) ? $node_notification_message : $translated_notification_message;
          $translated_mail_subject = !empty($node_notification_mail_sub) ? $node_notification_mail_sub : $translated_mail_subject;
          $translated_mail_message = !empty($node_notification_mail_message) ? $node_notification_mail_message : $translated_mail_message;
        }
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
      if (is_array($content_types) && in_array($bundle, $content_types)) {
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
    $this->eventDispatcher->dispatch($event, VactoryNotificationsToastEvent::EVENT_NAME);
  }

}
