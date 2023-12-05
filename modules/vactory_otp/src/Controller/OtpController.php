<?php

namespace Drupal\vactory_otp\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides otp endpoints.
 */
class OtpController extends ControllerBase {

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Otp Login Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new ApiFlagging object.
   */
  public function __construct(TimeInterface $time, ConfigManager $config_manager, LoggerChannelFactoryInterface $logger) {
    $this->time = $time;
    $this->config = $config_manager->getConfigFactory()->getEditable('vactory_otp.login_settings');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('config.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Generate OTP.
   */
  public function generateOtp(Request $request, $value) {
    $login_field = $this->config->get('login_field');
    $phone_field = $this->config->get('phone_field');
    $email_field = $this->config->get('email_field');
    $canal = $this->config->get('canal');

    $this->logger->get('vactory_otp')->info("OTP requested for : {$value}");

    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition($login_field, trim($value));
    $ids = $query->execute();
    if (!is_array($ids) || count($ids) !== 1) {
      return new JsonResponse([
        'error_message' => 'Invalid user',
      ], 400);
    }
    $user_id = reset($ids);
    $user = User::load($user_id);
    if (!$user instanceof UserInterface) {
      return new JsonResponse([
        'error_message' => 'Invalid user',
      ], 400);
    }
    // Check cooldown.
    $old_otp = $user->get('otp')->value;
    if ($old_otp) {
      $old_otp = json_decode($old_otp, TRUE);
      $timestamp = $old_otp['timestamp'];
      $cooldown = \Drupal::config('vactory_otp.settings')->get('cooldown');
      if ($this->time->getRequestTime() - $timestamp < $cooldown) {
        return new JsonResponse([
          'error_message' => $this->t('You have to wait @seconds seconds before you can request another OTP.', ['@seconds' => $cooldown]),
        ], 400);
      }
    }
    $otp = '';
    if ($canal == 'phone') {
      $sms_phone = $user->get($phone_field)->value;
      $otp = \Drupal::service('vactory_otp.send_otp')->sendSmsOtp($sms_phone);
    }
    if ($canal == 'email') {
      $email = $user->get($email_field)->value;
      $otp = \Drupal::service('vactory_otp.send_otp')->sendMailOtp($email);
    }
    if (!$otp) {
      return new JsonResponse([
        'error_message' => 'Cannot send SMS',
      ], 400);
    }
    $user_otp = [
      'otp' => $otp,
      'timestamp' => time(),
    ];
    $user->set('otp', json_encode($user_otp));
    try {
      $user->save();
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error_message' => 'Error occurred',
      ], 400);
    }
    return new JsonResponse([
      'uid' => $user->id(),
    ]);

  }

  /**
   * Validate OTP.
   */
  public function validateOtp(Request $request) {
    // Get body.
    $body = json_decode($request->getContent(), TRUE);

    // Validate body.
    if (!isset($body['otp']) || !isset($body['uid'])) {
      return new JsonResponse([
        'message' => $this->t('error occurred'),
      ], 400);
    }

    // Load user.
    $user = User::load($body['uid']);
    if (!$user instanceof UserInterface) {
      return new JsonResponse([
        'message' => $this->t('error occurred'),
      ], 400);
    }

    // Check OTP.
    $user_otp = $user->get('otp')->value;
    if ($user_otp) {
      $otp_expiration = $this->config->get('expiration');
      $user_otp = json_decode($user_otp, TRUE);
      $request_time = $this->time->getRequestTime();
      $otp_time = $user_otp['timestamp'];
      if ($user_otp['otp'] == $body['otp'] && $request_time - $otp_time <= $otp_expiration) {
        return new JsonResponse([
          'timestamp' => $request_time,
          'hash' => user_pass_rehash($user, $request_time),
        ]);
      }
    }

    return new JsonResponse([
      'message' => $this->t('OTP incorrect or expired'),
    ], 400);
  }

}
