<?php

namespace Drupal\vactory_push_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\vactory_push_notification\Entity\Subscription;
use Drupal\vactory_push_notification\KeysHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a push notification responses.
 */
class PushNotificationController extends ControllerBase
{

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * PushNotificationController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   * @param \Drupal\vactory_push_notification\KeysHelper $keysHelper
   *   The notification keys helper service.
   */
  public function __construct(ModuleHandler $moduleHandler, KeysHelper $keysHelper)
  {
    $this->moduleHandler = $moduleHandler;
    $this->keysHelper = $keysHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('module_handler'),
      $container->get('vactory_push_notification.keys_helper')
    );
  }

  /**
   * Accepts a user confirmation for notifications subscribe.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   When public and private keys are empty.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   When required parameter (key, token, endpoint) is missing.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function subscribe(Request $request)
  {
    $parameters = json_decode($request->getContent(), true);

    $token = $parameters['token'] ?? null;
    $endpoint = $parameters['endpoint'] ?? null;
    $app_id = $parameters['app_id'] ?? null;
    $userId = \Drupal::currentUser()->id();

    if (!empty($token) && !empty($endpoint)  && !empty($app_id)) {
      $ids = \Drupal::entityQuery('vactory_wpn_subscription')
        ->condition('endpoint', $endpoint)
        ->condition('app_id', $app_id)
        ->condition('token', $token)
        ->condition('user', $userId)
        ->execute();
      if (empty($ids)) {
        $subscription = Subscription::create([
          'endpoint' => $endpoint,
          'token'    => $token,
          'user'    => $userId,
          'app_id'    => $app_id,
        ]);
        $subscription->save();
      }
    } else {
      throw new BadRequestHttpException();
    }

    return new JsonResponse(['status' => TRUE]);
  }
}
