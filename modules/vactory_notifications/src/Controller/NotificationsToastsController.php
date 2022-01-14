<?php

namespace Drupal\vactory_notifications\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Notification toast controller.
 */
class NotificationsToastsController extends ControllerBase {

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Get current user notifications toasts if exist.
   */
  public function getNotificationsToast() {
    $current_user = \Drupal::currentUser();
    $user = $this->entityTypeManager->getStorage('user')
      ->load($current_user->id());
    $user_toasts = Json::decode($user->get('field_notification_toast')->value);
    $notifications = [];
    if (!empty($user_toasts)) {
      $notifications = $this->entityTypeManager->getStorage('notifications_entity')
        ->loadMultiple($user_toasts);
      $user->set('field_notification_toast', [])
        ->save();
    }
    $template = [
      '#theme' => 'vactory_notifications_toasts',
      '#notifications' => $notifications,
    ];
    return new JsonResponse(['content' => $this->renderer->render($template)]);
  }
}