<?php

namespace Drupal\vactory_notifications\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Vactory Notifications routes.
 */
class VactoryNotificationsToasts extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Notifications config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $notificationsConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    $instance->database = $container->get('database');
    $instance->notificationsConfig = $instance->config('vactory_notifications.settings');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function getToasts() {
    // Get notifications module settings.
    $notification_config = $this->notificationsConfig;
    $empty_response = Json::encode([]);
    if (!$notification_config->get('enable_toast')) {
      echo $empty_response;
      exit;
    }
    // Get Current user and current langcode from post params.
    $uid = $this->currentUser()->id();
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $authenticated_user = $this->database->query('SELECT uid FROM {sessions} where uid = :uid', [':uid' => $uid]);
    // The given user should be authenticated.
    if (!$authenticated_user) {
      return new JsonResponse([]);
    }
    $authenticated_user = count($authenticated_user->fetchAll());
    if ($authenticated_user <= 0) {
      return new JsonResponse([]);
    }
    $user_toasts = $this->database->query('SELECT field_notification_toast_value FROM {user__field_notification_toast} WHERE entity_id=:uid', [':uid' => $uid]);
    if (!empty($user_toasts)) {
      $user_toasts = $user_toasts->fetchAll();
      $user_toasts = Json::decode($user_toasts[0]->field_notification_toast_value);
      if (!empty($user_toasts)) {
        $viewed_toasts = $this->database->query('SELECT field_notifications_viewed_toast_value FROM {user__field_notifications_viewed_toast} WHERE entity_id=:uid', [':uid' => $uid]);
        if (!empty($viewed_toasts)) {
          $viewed_toasts = $viewed_toasts->fetchAll();
          $viewed_toasts = Json::decode($viewed_toasts[0]->field_notifications_viewed_toast_value);
        }
        $viewed_toasts = $viewed_toasts ?? [];
        $user_toasts = array_diff($user_toasts, $viewed_toasts);
        if (empty($user_toasts)) {
          return new JsonResponse([]);
        }
        $viewed_toasts= array_merge($viewed_toasts, $user_toasts);
        $viewed_toasts = array_slice($viewed_toasts, -8);
        $user = $this->entityTypeManager()->getStorage('user')->load($uid);
        $user->set('field_notifications_viewed_toast', Json::encode($viewed_toasts))
          ->save();
        if (!empty($user_toasts)) {
          $notifications = $this->database->query('SELECT * FROM {notifications_entity_field_data} where id IN (:user_toasts[]) AND langcode = :langcode', [
            ':user_toasts[]' => $user_toasts,
            ':langcode' => $langcode,
          ]);
          if ($notifications) {
            $notifications = $notifications->fetchAll();
            $template = [
              '#theme' => 'vactory_notifications_toasts',
              '#notifications' => $notifications,
            ];
            $renderer = $this->renderer;
            $content = $this->renderer->executeInRenderContext(new RenderContext(), static function () use ($template, $renderer) {
              return $renderer->render($template);
            });

            return new JsonResponse(['content' => $content]);
          }
        }
      }
    }
    return new JsonResponse([]);
  }

}
