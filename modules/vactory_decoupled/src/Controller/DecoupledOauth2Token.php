<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\simple_oauth\Controller\Oauth2Token;
use Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface;
use Drupal\user\UserFloodControl;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Get oauth2 token.
 *
 * @package Drupal\vactory_decoupled\Controller
 */
class DecoupledOauth2Token extends Oauth2Token {

  /**
   * User flood control.
   *
   * @var \Drupal\user\UserFloodControl
   */
  protected $flood;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * User limit.
   *
   * @var string
   */
  protected $userLimit;

  /**
   * User window.
   *
   * @var string
   */
  protected $userWindow;

  /**
   * User ip limit.
   *
   * @var string
   */
  protected $userIpLimit;

  /**
   * User ip window.
   *
   * @var string
   */
  protected $userIpWindow;

  /**
   * DecoupledOauth2Token constructor.
   */
  public function __construct(
    Oauth2GrantManagerInterface $grant_manager,
    ClientRepositoryInterface $client_repository,
    UserFloodControl $flood,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($grant_manager, $client_repository);
    $this->flood = $flood;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;

    if ($this->moduleHandler->moduleExists('flood_control')) {
      $this->userLimit = $this->configFactory->get('user.flood')
        ->get('user_limit') ?? 5;
      $this->userWindow = $this->configFactory->get('user.flood')
        ->get('user_window') ?? 300;

      $this->userIpLimit = $this->configFactory->get('user.flood')
        ->get('ip_limit') ?? 50;

      $this->userIpWindow = $this->configFactory->get('user.flood')
        ->get('ip_window') ?? 3600;

    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.oauth2_grant.processor'),
      $container->get('simple_oauth.repositories.client'),
      $container->get('user.flood_control'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Processes POST requests to /oauth/token.
   */
  public function token(ServerRequestInterface $request) {
    $response = parent::token($request);

    $flood_message_display = $this->configFactory->get('vactory_flood_control.settings')->get('flood_message_display') ?? FALSE;
    $flood_user_message = 'There have been more than %s failed login attempts for this account. It is temporarily blocked. Try again later or request a new password.';
    $flood_ip_message = "Trop d'échecs de connexion à partir de votre adresse IP. Cette adresse IP est temporairement bloquée. Réessayer ultérieurement";
    if (!$flood_message_display) {
      $flood_user_message = 'The account information provided was invalid.';
      $flood_ip_message = 'The account information provided was invalid.';
    }

    $body = $request->getParsedBody();
    if (!empty($body['username']) && !empty($body['password'])) {
      $isEmail = strpos($body['username'], '@') !== FALSE;

      $property = $isEmail ? 'mail' : 'name';
      $account_search = $this->entityTypeManager
        ->getStorage('user')
        ->loadByProperties([$property => $body['username']]);
      if ($account = reset($account_search)) {
        $isAllowed = $this->flood->isAllowed('user.failed_login_user', $this->userLimit, $this->userWindow, $account->id());
        $isAllowedIp = $this->flood->isAllowed('user.failed_login_ip', $this->userIpLimit, $this->userIpWindow);
        if (!$isAllowed || !$isAllowedIp) {
          $responseError = [
            'error' => 'flood_control_error',
            'message' => sprintf($flood_user_message, $this->userLimit),
          ];
          if (!$isAllowedIp) {
            $responseError['message'] = $flood_ip_message;
          }
          return new JsonResponse($responseError, 400);
        }
        if ($response->getStatusCode() !== 200) {
          $this->logAuthFailure($body);
          $this->flood->register('user.failed_login_user', $this->userWindow, $account->id());
          $this->flood->register('user.failed_login_ip', $this->userIpWindow);
        }

        if ($response->getStatusCode() === 200) {
          $this->logAuthSuccess($body);
        }

      }
      else {
        $this->logAuthFailure($body);
      }

    }

    return $response;
  }

  /**
   * Log authentication failures.
   */
  protected function logAuthFailure($body) {
    if (\Drupal::moduleHandler()->moduleExists('vactory_security_review')) {
      $is_failed_login_log_enabled = \Drupal::config('security_review.checks')->get('log_failed_auth');
      $ip = \Drupal::request()->getClientIp();
      if ($is_failed_login_log_enabled) {
        \Drupal::logger('user')->info("Login attempt failed from: <br>IP: {$ip}<br>Username: {$body['username']}");
      }
    }
  }

  /**
   * Log authentication success.
   */
  protected function logAuthSuccess($body) {
    if (\Drupal::moduleHandler()->moduleExists('vactory_security_review')) {
      $is_failed_login_log_enabled = \Drupal::config('security_review.checks')->get('failed_auth_log');
      if ($is_failed_login_log_enabled) {
        \Drupal::logger('user')->info("Session opened for {$body['username']} via Oauth2");
      }
    }
  }

}
