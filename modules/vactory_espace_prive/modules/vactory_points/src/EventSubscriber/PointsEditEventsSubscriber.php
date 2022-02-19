<?php

namespace Drupal\vactory_points\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\vactory_notifications\Services\VactoryNotificationsService;
use Drupal\vactory_points\Event\VactoryPointsEditEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Points Edit event subscriber class.
 */
class PointsEditEventsSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The concerned user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private $entityRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Notifications service.
   *
   * @var \Drupal\vactory_notifications\Services\VactoryNotificationsService
   */
  protected $notificationsManager;

  /**
   * Points Edit event subscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(
    AccountProxyInterface $account,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    VactoryNotificationsService $notificationsManager
  ) {
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->notificationsManager = $notificationsManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      VactoryPointsEditEvent::EVENT_NAME => 'editUserPoints',
    ];
  }

  /**
   * Edit user points event handler.
   */
  public function editUserPoints(VactoryPointsEditEvent $event) {
    $action = $event->getAction();
    $entity = $event->getEntity();
    $this->user = $event->getConcernedUser();
    if (!$this->user) {
      $this->user = $this->entityTypeManager->getStorage('user')
        ->load($this->account->id());
    }

    $user_performed_actions = !empty($this->user->get('field_no_repeated_actions')->value) ? Json::decode($this->user->get('field_no_repeated_actions')->value) : [];
    $user_points = !empty($this->user->get('field_user_points')->value) ? $this->user->get('field_user_points')->value : 0;
    $old_user_points = $user_points;
    $rules = $this->getSatisfiedRulesByAction($event, $action, $user_performed_actions);
    $action = strpos($action, 'flag/') === 0 || strpos($action, 'unflag/') === 0 ? explode('/', $action)[0] : $action ;
    if (!empty($rules)) {
      foreach ($rules as $index => $rule) {
        if ($rule['action']['no_repeat']) {
          if (
            isset($entity) && isset($user_performed_actions[$action]) && in_array($entity->id(), $user_performed_actions[$action]) ||
            !isset($entity) && isset($user_performed_actions[$action])
          ) {
            continue;
          }
          else {
            // Add node id to processed action node list.
            $user_performed_actions[$action][] = isset($entity) ? $entity->id() : TRUE;
            $this->user->set('field_no_repeated_actions', Json::encode($user_performed_actions));
          }
        }
        $points = (int) $rule['points_info']['points'];
        $user_points = $rule['points_info']['operation'] === 'decrement' ? $user_points - $points : $user_points + $points;
        if ($points > 0 && $this->moduleHandler->moduleExists('vactory_notifications')) {
          $this->createNotification($event, $rule, $this->user, $index);
        }
      }
    }
    if ($old_user_points !== $user_points) {
      $this->user->set('field_user_points', $user_points)
        ->save();
    }
  }

  /**
   * Get satisfied rules by action.
   */
  public function getSatisfiedRulesByAction(VactoryPointsEditEvent $event, $action, $user_performed_actions) {
    $config = $this->configFactory->getEditable('vactory_points.settings');
    $rules = $config->get('rules');
    $entity = $event->getEntity();
    $filtered_rules = [];
    $flag_id = '';
    if (strpos($action, 'flag/') === 0 || strpos($action, 'unflag/') === 0) {
      $action_pieces = explode('/', $action);
      $action = $action_pieces[0];
      $flag_id = $action_pieces[1];
    }

    foreach ($rules as $index => $rule) {
      $action_value = $rule['action']['value'] === 'other' ? $rule['action']['other_action_value'] : $rule['action']['value'];
      if ($action_value === $action) {
        if (!empty($flag_id) && !in_array($flag_id, $rule['action']['concerned_flags'], TRUE)) {
          continue;
        }
        $role_match = count(array_intersect($this->user->getRoles(), $rule['roles'])) > 0 || in_array('all', $rule['roles']);
        $node_type_match = (isset($entity) && in_array($entity->bundle(), $rule['node_type'])) || in_array('all', $rule['node_type']);
        $no_repeat = $rule['action']['no_repeat'];
        $entity_action_performed = isset($entity) && isset($user_performed_actions[$action]) && in_array($entity->id(), $user_performed_actions[$action]);
        $none_entity_action_performed = !isset($entity) && isset($user_performed_actions[$action]);
        $action_processed = $no_repeat && ($entity_action_performed || $none_entity_action_performed);
        if ($role_match && $node_type_match && !$action_processed) {
          $filtered_rules[$index] = $rule;
        }
      }
    }
    return $filtered_rules;
  }

  /**
   * Create new notification.
   */
  private function createNotification(VactoryPointsEditEvent $event, array $rule, UserInterface $user, $index) {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $config = $this->configFactory->getEditable('vactory_points.settings');
    $notification_config = $this->configFactory->get('vactory_notifications.settings');
    $title = $config->get('notifications')[$rule['points_info']['operation']]['notification_title'];
    $message = $config->get('notifications')[$rule['points_info']['operation']]['notification_message'];
    $entity = $event->getEntity();
    $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
    $points = $rule['points_info']['points'];
    $action_label = $rule['action']['action_label'];
    $entity_title = isset($entity) ? $entity->label() : '';
    $title = $this->replaceTokens($title, $points, $action_label, $entity_title);
    $message = $this->replaceTokens($message, $points, $action_label, $entity_title);
    $message = empty($entity_title) ? str_replace(['«', '»'], '', $message) : $message;
    $notification_data = [
      'type' => 'notification_entity',
      'name' => $title,
      'user_id' => $user->id(),
      'notification_related_content' => $entity instanceof NodeInterface ? $entity->id() : NULL,
      'notification_message' => $message,
      'status' => TRUE,
      'notification_concerned_users' => Json::encode([$user->id()]),
      'notification_viewers' => Json::encode([]),
    ];
    $notification = $this->entityTypeManager->getStorage('notifications_entity')
      ->create($notification_data);
    $notification->save();
    if ($notification_config->get('enable_toast')) {
      // Trigger notification toast.
      $this->notificationsManager->triggerNotificationsToast($notification);
    }
    // Notifications auto translation feature.
    $is_auto_translated = (boolean) $notification_config->get('auto_translation');
    if ($is_auto_translated) {
      $this->translateNotification($notification, $event, $rule, $index);
    }
  }

  /**
   * Translate the given notification.
   */
  private function translateNotification($notification, VactoryPointsEditEvent $event, array $rule, $index) {
    $enabled_languages = $this->languageManager->getLanguages();
    $entity = $event->getEntity();
    foreach ($enabled_languages as $langcode => $language) {
      if (!$language->isDefault()) {
        $translated_entity = isset($entity) ? $this->entityRepository->getTranslationFromContext($entity, $langcode) : NULL;
        $vactory_points_config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_points.settings');
        $vactory_points_config = $this->configFactory->get('vactory_points.settings');
        $points = $rule['points_info']['points'];
        $rules = $vactory_points_config_translation->get('rules') ?? $vactory_points_config->get('rules');
        $action_label = $rules[$index]['action']['action_label'];
        $entity_title = $translated_entity ? $translated_entity->label() : '';
        $notifications = $vactory_points_config_translation->get('notifications') ?? $vactory_points_config->get('notifications');
        $translated_notification_title = $notifications[$rule['points_info']['operation']]['notification_title'];
        $translated_notification_message = $notifications[$rule['points_info']['operation']]['notification_message'];
        $translated_notification_title = $this->replaceTokens($translated_notification_title, $points, $action_label, $entity_title);
        $translated_notification_message = $this->replaceTokens($translated_notification_message, $points, $action_label, $entity_title);
        $entity_title_wrapper = ['«', '»'];
        $translated_notification_message = empty($entity_title) ? str_replace($entity_title_wrapper, '', $translated_notification_message) : $translated_notification_message;
        $translated_notification = $notification->addTranslation($langcode);
        $translated_notification->name = $translated_notification_title;
        $translated_notification->notification_message = $translated_notification_message;
        $translated_notification->save();
      }
    }
  }

  /**
   * Replace notification tokens.
   */
  private function replaceTokens($subject, $points, $action_label, $entity_title) {
    $token_replace = [
      '@points' => $points,
      '@action_label' => $action_label,
      '@entity_title' => $entity_title,
    ];
    return str_replace(array_keys($token_replace), array_values($token_replace), $subject);
  }

}
