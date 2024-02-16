<?php

namespace Drupal\vactory_two_factor_authentication\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for vactory_two_factor_authentication routes.
 */
class VactoryTwoFactorAuthenticationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Config.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $config;

  /**
   * Constructs a new EventFormMailService.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   */
  public function __construct(TimeInterface $time, ConfigFactory $config) {
    $this->time = $time;
    $this->config = $config;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('config.factory')
    );
  }

  /**
   * Builds the response.
   */
  public function sendTwoFactorAuthenticationCode(Request $request) {
    $email = $request->query->get('email', '');
    if (empty($email)) {
      return new JsonResponse([
        'message' => "Parameter mail is required",
        'status' => 401,
      ], 401);
    }
    // Load user.
    $user = user_load_by_mail($email);
    if (!isset($user)) {
      return new JsonResponse([
        'message' => "User doesn't exist",
        'status' => 401,
      ], 401);
    }

    $otpService = \Drupal::service('vactory_otp.send_otp');
    $config = $this->config
      ->getEditable('vactory_two_factor_authentication.settings');
    $code_otp = '';
    $provider = $config->get('type_2fa');
    switch ($provider) {
      case 'mail':
        $mail = $user->getEmail();
        $code_otp = $otpService->sendOtpByMail('', $mail, '', '');
        break;

      case 'phone':
        $user_phone = $user->get('field_telephone')->value;
        if (!isset($user) || empty($user_phone)) {
          return new JsonResponse([
            'message' => "User phone is not set yet",
            'status' => 401,
          ], 401);
        }
        $user_phone = preg_replace("/^0/", "212", $user_phone);
        $code_otp = $otpService->sendOtpBySms($user_phone);
        break;
    }

    if ($code_otp !== '') {
      // Update user.
      $user->set('user_2fa_data', json_encode([
        'otp' => $code_otp,
        'last_code_otp_sent' => $this->time->getCurrentTime(),
      ]));
      $user->save();
      return new JsonResponse([
        'provider' => $provider,
        'max_attempt' => $config->get('max_attempt_per_login') ?? 5,
        'message' => 'Code has been sent to the concerned user',
        'status' => 200,
      ], 200);
    }

    return new JsonResponse([
      'message' => 'The server cannot not process the request',
      'status' => 400,
    ], 400);

  }

  /**
   * Verify otp code.
   */
  public function verifyOtpCode(Request $request) {
    $email = $request->query->get('email', '');
    $code = $request->query->get('code', '');
    if (empty($email) || empty($code)) {
      return new JsonResponse([
        'message' => "Parameter mail && code is required",
        'status' => 401,
      ], 401);
    }

    // Load user.
    $user = user_load_by_mail($email);
    if (!isset($user)) {
      return new JsonResponse([
        'message' => "User doesn't exist",
        'status' => 401,
      ], 401);
    }
    $user_2fa_data = json_decode($user->get('user_2fa_data')->value, TRUE);

    if (isset($user_2fa_data) && $user_2fa_data['otp'] == (int) $code) {
      $config = $this->config
        ->getEditable('vactory_two_factor_authentication.settings');
      $expiration = $config->get('cooldown');
      if ($this->time->getCurrentTime() - $user_2fa_data['last_code_otp_sent'] <= $expiration) {
        // Update user.
        $user->set('user_2fa_data', NULL);
        $user->save();
        return new JsonResponse([
          'message' => 'Success',
          'status' => 200,
        ], 200);
      }
    }

    return new JsonResponse([
      'message' => "Code doesn't match Or Code already expired",
      'status' => 401,
    ], 401);

  }

}