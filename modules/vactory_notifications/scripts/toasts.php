<?php
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

define('DRUPAL_DIR', __DIR__ . '/../../../../../..');

// Bootstrap to initialize container.
$autoloader = require DRUPAL_DIR . '/autoload.php';
require_once DRUPAL_DIR . '/core/includes/bootstrap.inc';
$request = Request::createFromGlobals();
Settings::initialize(dirname(dirname(DRUPAL_DIR)), DrupalKernel::findSitePath($request), $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();
$container = $kernel->getContainer();
// Get database service from container.
$db = $container->get('database');
// Get twig service from container;
$twig = $container->get('twig');
// Get notifications module settings.
$notification_config = Drupal::config('vactory_notifications.settings');
header('Content-Type: application/json; charset=utf-8');
$empty_response = json_encode([]);
if (!$notification_config->get('enable_toast') || !isset($_POST['langcode']) || !isset($_POST['uid'])) {
  echo $empty_response;
  exit;
}
// Get Current user and current langcode from post params.
$uid = $_POST['uid'];
$langcode = $_POST['langcode'];
$authenticated_user = $db->query('SELECT uid FROM {sessions} where uid = :uid', [':uid' => $uid]);
// The given user should be authenticated.
if (!$authenticated_user) {
  echo $empty_response;
  exit;
}
$authenticated_user = count($authenticated_user->fetchAll());
if ($authenticated_user <= 0) {
  echo $empty_response;
  exit;
}
$user_toasts = $db->query('SELECT field_notification_toast_value FROM {user__field_notification_toast} WHERE entity_id=:uid', [':uid' => $uid]);
if (!empty($user_toasts)) {
  $user_toasts = $user_toasts->fetchAll();
  $user_toasts = json_decode($user_toasts[0]->field_notification_toast_value);
  if (!empty($user_toasts)) {
    $viewed_toasts = $db->query('SELECT field_notifications_viewed_toast_value FROM {user__field_notifications_viewed_toast} WHERE entity_id=:uid', [':uid' => $uid]);
    if (!empty($viewed_toasts)) {
      $viewed_toasts = $viewed_toasts->fetchAll();
      $viewed_toasts = json_decode($viewed_toasts[0]->field_notifications_viewed_toast_value);
    }
    $viewed_toasts = $viewed_toasts ?? [];
    $user_toasts = array_diff($user_toasts, $viewed_toasts);
    if (empty($user_toasts)) {
      echo $empty_response;
      exit;
    }
    $viewed_toasts= array_merge($viewed_toasts, $user_toasts);
    $viewed_toasts = array_slice($viewed_toasts, -8);
    $user = User::load($uid);
    $user->set('field_notifications_viewed_toast', json_encode($viewed_toasts))
      ->save();
    if (!empty($user_toasts)) {
      $notifications = $db->query('SELECT * FROM {notifications_entity_field_data} where id IN (:user_toasts[]) AND langcode = :langcode', [
        ':user_toasts[]' => $user_toasts,
        ':langcode' => $langcode,
      ]);
      if ($notifications) {
        $notifications = $notifications->fetchAll();
        $template = [
          '#theme' => 'vactory_notifications_toasts',
          '#notifications' => $notifications,
        ];
        $default_template = 'profiles/contrib/vactory_starter_kit/modules/vactory_notifications/templates/notifications-toast.html.twig';
        $template_file = $notification_config->get('toast_template');
        $template = file_get_contents($template_file ?? $default_template);
        $variables = [
          'notifications' => $notifications,
          'langcode' => $langcode,
        ];
        $content = $twig->renderInline($template, $variables)->__toString();
        echo json_encode(['content' => trim(preg_replace('/\t+/', '', $content))]);
        exit;
      }
    }
  }
}
echo $empty_response;
exit;
