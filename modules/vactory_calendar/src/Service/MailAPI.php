<?php

namespace Drupal\vactory_calendar\Service;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

trait MailAPI {
  /**
   * Function to send mail.
   */
  public function sendMailjet($subject, $mail_body, $from, $dest) {

    $mailConfig = \Drupal::service('config.factory')->get('vactory_calendar.settings');
    $mailLogger = \Drupal::service('logger.factory')->get('clubafrique_mail');

    $username = !empty($mailConfig->get('api_key')) ? $mailConfig->get('api_key') : '';
    $password = !empty($mailConfig->get('api_secret')) ? $mailConfig->get('api_secret') : '';
    if (empty($username) || empty($password)) return FALSE;
    $receivers = [];
    foreach ($dest as $to) {
      $receivers[] = [
        'Email' => $to,
        'Name' => $to,
      ];
    }
    $body = [
      'Messages' => [
        [
          'From' => [
            'Email' => $from,
            'Name' => "Vactory Dev",
          ],
          'To' => $receivers,
          'Subject' => $subject,
          'HTMLPart' => $mail_body,
        ],
      ],
    ];
    try {
      $client = \Drupal::service('http_client');
      $response = $client->request('POST', 'https://api.mailjet.com/v3.1/send', [
        'json' => $body,
        'auth' => [
          $username,
          $password,
        ],
      ]);
      if ($response->getStatusCode() === 200) {
        $mailLogger->notice("Email sent successfully: " . $response->getBody());
        return TRUE;
      }
    }
    catch (\Exception $e) {
      $mailLogger->error("Erreur lors de l'envoi du mail : " . $e->getMessage());
    }
    return FALSE;
  }

  /**
   * @param $subject
   * @param $mail_body
   * @param $from
   * @param $dest
   *
   * @return bool
   */
  public function sendMailGun($subject, $mail_body, $from, $dest) {

    $mailConfig = \Drupal::service('config.factory')->get('vactory_calendar.settings');
    $mailLogger = \Drupal::service('logger.factory')->get('clubafrique_mail');

    $username = 'api';
    $password = !empty($mailConfig->get('mailgun_api_key')) ? $mailConfig->get('mailgun_api_key') : '';
    $domainMail = !empty($mailConfig->get('mailgun_domain')) ? $mailConfig->get('mailgun_domain') : '';
    if (empty($password)) return FALSE;
    $body = [
      'from' => $from,
      'to' => $dest,
      'subject' => $subject,
      'text' => $mail_body
    ];
    try {
      $client = \Drupal::service('http_client');

      $response = $client->request('POST', "https://api.mailgun.net/v3/$domainMail/messages", [
        'form_params' => $body,
        'auth' => [
          $username,
          $password,
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $mailLogger->notice("Email sent successfully: " . $response->getBody());
        return TRUE;
      }
    }
    catch (\Exception $e) {
      $mailLogger->error("Erreur lors de l'envoi du mail : " . $e->getMessage());
    }
    return FALSE;
  }

}
