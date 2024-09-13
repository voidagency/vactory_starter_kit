<?php

namespace Drupal\vactory_decoupled\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\ResourceResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use ReCaptcha\ReCaptcha;
use Drupal\recaptcha\ReCaptcha\RequestMethod\Drupal8Post;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;

/**
 * ReCaptcha subscriber for JSON:API requests.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 */
class JsonApiReCaptchaSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Constructs a new JsonapiReCaptchaSubscriber.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger) {
    $this->config = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

  /**
   * Returns response when site is in maintenance mode and user is not exempt.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();

    if (strtoupper($request->getMethod()) !== "POST") {
      return;
    }

    $secure_routes_raw = $this->config->get('vactory_decoupled.settings')
      ->get('routes', '');
    $secure_routes = is_string($secure_routes_raw) ? explode("\n", $secure_routes_raw) : [];
    $secure_routes = array_map(function ($route) {
      return trim($route);
    }, $secure_routes);

    $path = $request->getPathInfo();
    $is_path_protected = array_search($path, $secure_routes) !== FALSE;

    if (!$is_path_protected) {
      return;
    }

    $payload = json_decode($request->getContent(), TRUE);
    $recaptcha_token = isset($payload['g-recaptcha-response']) ? $payload['g-recaptcha-response'] : $request->get('g-recaptcha-response', "");
    $isValid = $this->isValidRecaptchaToken($recaptcha_token);

    // Bail out.
    if ($isValid) {
      return;
    }

    $http_exception = new EntityAccessDeniedHttpException(NULL, AccessResult::forbidden(), "/data/attributes/captcha", sprintf('The current reCaptcha challenge failed.', "captcha"));
    $document = new JsonApiDocumentTopLevel(new ErrorCollection([$http_exception]), new NullIncludedData(), new LinkCollection([]));
    $response = new ResourceResponse($document, 403, [
      'Content-Type' => 'application/vnd.api+json',
    ]);
    // Calling RequestEvent::setResponse() also stops propagation of event.
    $event->setResponse($response);
  }

  /**
   * CAPTCHA Callback; Validates the reCAPTCHA code.
   */
  private function isValidRecaptchaToken($recaptcha_token) {
    $config = $this->config->get('recaptcha.settings');

    $recaptcha_secret_key = $config->get('secret_key');
    if (empty($recaptcha_token) || empty($recaptcha_secret_key)) {
      return FALSE;
    }

    $recaptcha = new ReCaptcha($recaptcha_secret_key, new Drupal8Post(\Drupal::httpClient()));

    // Ensures the hostname matches. Required if "Domain Name Validation" is
    // disabled for credentials.
    if ($config->get('verify_hostname')) {
      $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME']);
    }

    $resp = $recaptcha->verify(
      $recaptcha_token,
      \Drupal::request()->getClientIp()
    );

    if ($resp->isSuccess()) {
      // Verified!
      return TRUE;
    }
    else {
      $error_codes = [
        'action-mismatch' => t('Expected action did not match.'),
        'apk_package_name-mismatch' => t('Expected APK package name did not match.'),
        'bad-response' => t('Did not receive a 200 from the service.'),
        'bad-request' => t('The request is invalid or malformed.'),
        'connection-failed' => t('Could not connect to service.'),
        'invalid-input-response' => t('The response parameter is invalid or malformed.'),
        'invalid-input-secret' => t('The secret parameter is invalid or malformed.'),
        'invalid-json' => t('The json response is invalid or malformed.'),
        'missing-input-response' => t('The response parameter is missing.'),
        'missing-input-secret' => t('The secret parameter is missing.'),
        'hostname-mismatch' => t('Expected hostname did not match.'),
        'unknown-error' => t('Not a success, but no error codes received!'),
      ];
      $info_codes = [
        'challenge-timeout' => t('Challenge timeout.'),
        'score-threshold-not-met' => t('Score threshold not met.'),
        'timeout-or-duplicate' => t('The challenge response timed out or was already verified.'),
      ];
      foreach ($resp->getErrorCodes() as $code) {
        if (isset($info_codes[$code])) {
          $this->logger->get('reCAPTCHA web service')
            ->info('@info', ['@info' => $info_codes[$code]]);
        }
        else {
          if (!isset($error_codes[$code])) {
            $code = 'unknown-error';
          }
          $this->logger->get('reCAPTCHA web service')
            ->error('@error', ['@error' => $error_codes[$code]]);
        }
      }
    }
    return FALSE;
  }

}
