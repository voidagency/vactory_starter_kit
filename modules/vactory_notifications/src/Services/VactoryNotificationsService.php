<?php

namespace Drupal\vactory_notifications\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Class VactoryNotificationsService.
 */
class VactoryNotificationsService {

  /**
   * User Service.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager.
   */

  protected $languageManager;


  public function __construct(MailManagerInterface $mail_manager , LanguageManagerInterface $language_manager ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
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




}
