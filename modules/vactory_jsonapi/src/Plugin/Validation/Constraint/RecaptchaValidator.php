<?php

namespace Drupal\vactory_jsonapi\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod\Drupal8Post;

/**
 * Validates the Recaptcha constraint.
 */
class RecaptchaValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a new RecaptchaValidator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $request = \Drupal::request();
    $method = strtoupper($request->getMethod());
    $is_admin = $this->currentUser->hasPermission('skip CAPTCHA');

    if (in_array($method, [
      'GET',
      'HEAD',
      'CONNECT',
      'TRACE',
      'OPTIONS',
    ], TRUE) || $is_admin) {
      return;
    }
    $raw_data = $request->getContent();
    $data = \Drupal\Component\Serialization\Json::decode($raw_data);
    $value = $data['data']['attributes']['g-recaptcha-response'] ?? '';

    if (empty($value)) {
      $this->context->buildViolation($constraint->required)
        ->atPath('g_recaptcha_response')
        ->setCode('factory-7a99-4df7-8ce9-46e416a1e60b')
        ->addViolation();
//      $this->context->addViolation($constraint->required, ['%value' => $value]);
    }
    else {
      if (!$this->isValid($value)) {
        $this->context->buildViolation($constraint->notValid)
          ->atPath('g-recaptcha-response')
          ->addViolation();
//        $this->context->addViolation($constraint->notValid, ['%value' => $value]);
      }
    }
  }

  /**
   * Is valid?
   *
   * @param string $value
   */
  private function isValid($value) {
    $config = \Drupal::config('recaptcha.settings');
    $recaptcha_secret_key = $config->get('secret_key');
    // Use Drupal::httpClient() to circumvent all issues with the Google library.
    $recaptcha = new ReCaptcha($recaptcha_secret_key, new Drupal8Post());

    // Ensures the hostname matches. Required if "Domain Name Validation" is
    // disabled for credentials.
    if ($config->get('verify_hostname')) {
      $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME']);
    }

    $resp = $recaptcha->verify(
      $value,
      \Drupal::request()->getClientIp()
    );

    return $resp->isSuccess();
  }

}
