<?php

namespace Drupal\vactory_otp\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\vactory_sms_sender\Services\VactorySmsSenderService;

/**
 * Contains otp senders (sms, mail).
 *
 * Class VactoryOtpService.
 */
class VactoryOtpService {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * User Service.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempstore;

  /**
   * The vactory otp store.
   *
   * @var mixed
   */
  protected $store;

  /**
   * SMS Sender Service.
   *
   * @var \Drupal\vactory_sms_sender\Services\VactorySmsSenderService
   */
  protected $smsSender;

  /**
   * Constructs a new EventFormMailService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager.
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   *   Config manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore
   *   Private tempstore.
   * @param \Drupal\vactory_sms_sender\Services\VactorySmsSenderService $vactorySmsSenderService
   *   Sms Sender Service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, MailManagerInterface $mail_manager, ConfigManager $config_manager, TimeInterface $time, PrivateTempStoreFactory $tempstore, VactorySmsSenderService $vactorySmsSenderService) {
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->configManager = $config_manager;
    $this->time = $time;
    $this->tempstore = $tempstore;
    $this->store = $tempstore->get('vactory_otp');
    $this->smsSender = $vactorySmsSenderService;
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
   * @param string $otp
   *   Otp.
   *
   * @return int
   *   The generated otp.
   *
   * @deprecated use sendMailOtp instead.
   */
  public function sendOtpByMail($subject, $to_mail, $mail_body = '', $otp = '') {
    $confiManagerFactory = $this->configManager->getConfigFactory();
    $langcode = $confiManagerFactory->get('system.site')->get('langcode');
    $config = $confiManagerFactory->getEditable('vactory_otp.settings');

    if ($last = $this->store->get('last_mail_otp_sent')) {
      $cd = $config->get('cooldown');
      if (($this->time->getCurrentTime() - $last) < $cd) {
        \Drupal::messenger()->addError($this->t('You have to wait @seconds seconds before you can send another email.', ['@seconds' => $cd - ($this->time->getCurrentTime() - $last)]));
        return FALSE;
      }
    }

    // Mail.
    $module = 'vactory_otp';
    $key = 'vactory_otp_mail_body';
    $to = $to_mail;
    $reply = FALSE;
    $send = TRUE;

    if (empty($mail_body)) {
      $mail_body = $config->get('default_mail_body');
    }

    if (empty($otp)) {
      $otp = rand(10000, 99999);
    }

    $message_body = [
      'text' => $mail_body,
      'subject' => $subject,
      'otp' => $otp,
    ];

    $theme_body = [
      '#theme' => 'vactory_otp_mail_body',
      '#body' => $message_body,
    ];

    $mail_body = \Drupal::service('renderer')->renderPlain($theme_body);
    $params['message'] = $mail_body;
    $params['subject'] = $subject;
    $params['options']['title'] = $subject;

    $mailManager = $this->mailManager;
    try {
      $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
      $this->store->set('last_mail_otp_sent', $this->time->getCurrentTime());
      return $otp;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_otp')
        ->error("Erreur lors de l'envoi de l'otp par mail : " . $e->getMessage());
    }

    return FALSE;
  }

  /**
   * Function to send sms.
   *
   * @param string $sms_phone
   *   Phone number.
   * @param string $sms_body
   *   Sms body (If empty, fallback to config's default sms body).
   * @param string $otp
   *   Otp.
   *
   * @return int
   *   The generated otp.
   *
   * @deprecated use sendSmsOtp instead.
   */
  public function sendOtpBySms($sms_phone, $sms_body = '', $otp = '') {
    $config = \Drupal::service('config.factory')->getEditable('vactory_otp.settings');
    $from = $config->get('from');
    $api_key = $config->get('api_key');
    $url = $config->get('url');
    $client = \Drupal::httpClient();

    if ($last = $this->store->get('last_sms_otp_sent')) {
      $cd = $config->get('cooldown');
      if (($this->time->getCurrentTime() - $last) < $cd) {
        \Drupal::messenger()->addError($this->t('You have to wait @seconds seconds before you can send another sms.', ['@seconds' => $cd - ($this->time->getCurrentTime() - $last)]));
        return FALSE;
      }
    }
    if (empty($sms_body)) {
      $sms_body = $config->get('default_sms_body');
    }

    if (empty($otp)) {
      $otp = rand(10000, 99999);
    }

    $data = [
      'from' => $from,
      'to' => $sms_phone,
      'text' => $sms_body . ' : ' . $otp,
    ];

    try {
      $request = $client->post($url, [
        'json' => $data,
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'Authorization' => 'App ' . $api_key,
        ],
      ]);
      \Drupal::logger('vactory_otp')->info('OTP envoyé.');
      $this->store->set('last_sms_otp_sent', $this->time->getCurrentTime());
      return $otp;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_otp')
        ->error("Erreur lors de l'envoi de l'otp par sms : " . $e->getMessage());
    }

    return FALSE;
  }

  /**
   * Send OTP via SMS.
   */
  public function sendSmsOtp($sms_phone, $sms_body = '', $otp = '') {
    $config = \Drupal::service('config.factory')->getEditable('vactory_otp.settings');

    if ($last = $this->store->get('last_sms_otp_sent')) {
      $cd = $config->get('cooldown');
      if (($this->time->getCurrentTime() - $last) < $cd) {
        \Drupal::messenger()->addError($this->t('You have to wait @seconds seconds before you can send another sms.', ['@seconds' => $cd - ($this->time->getCurrentTime() - $last)]));
        return FALSE;
      }
    }
    if (empty($sms_body)) {
      $sms_body = $config->get('default_sms_body');
    }

    if (empty($otp)) {
      $otp = rand(10000, 99999);
    }

    $sms_content = $sms_body . ' : ' . $otp;

    try {
      $sent = $this->smsSender->sendSms($sms_phone, $sms_content);
      if ($sent) {
        return $otp;
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }

  }

  /**
   * Send OTP via Email.
   */
  public function sendMailOtp($to_mail, $subject = '', $mail_body = '', $otp = '') {
    $confiManagerFactory = $this->configManager->getConfigFactory();
    $langcode = $confiManagerFactory->get('system.site')->get('langcode');
    $config = $confiManagerFactory->getEditable('vactory_otp.settings');

    if ($last = $this->store->get('last_mail_otp_sent')) {
      $cd = $config->get('cooldown');
      if (($this->time->getCurrentTime() - $last) < $cd) {
        \Drupal::messenger()->addError($this->t('You have to wait @seconds seconds before you can send another email.', ['@seconds' => $cd - ($this->time->getCurrentTime() - $last)]));
        return FALSE;
      }
    }

    // Mail.
    $module = 'vactory_otp';
    $key = 'vactory_otp_mail_body';
    $to = $to_mail;
    $reply = FALSE;
    $send = TRUE;

    if (empty($mail_body)) {
      $mail_body = $config->get('default_mail_body');
    }

    if (empty($subject)) {
      $subject = $config->get('default_mail_subject');
    }

    if (empty($otp)) {
      $otp = rand(10000, 99999);
    }

    $message_body = [
      'text' => $mail_body,
      'subject' => $subject,
      'otp' => $otp,
    ];

    $theme_body = [
      '#theme' => 'vactory_otp_mail_body',
      '#body' => $message_body,
    ];

    $mail_body = \Drupal::service('renderer')->renderPlain($theme_body);
    $params['message'] = $mail_body;
    $params['subject'] = $subject;
    $params['options']['title'] = $subject;

    $mailManager = $this->mailManager;
    try {
      $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
      $this->store->set('last_mail_otp_sent', $this->time->getCurrentTime());
      return $otp;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_otp')
        ->error("Erreur lors de l'envoi de l'otp par mail : " . $e->getMessage());
    }

    return FALSE;
  }

}
