<?php

namespace Drupal\vactory_points\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vactory_points\Services\VactoryPointsManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;

/**
 * Flag/Unflag Event subscriber class.
 */
class FlagEventsSubscriber implements EventSubscriberInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Vactory points manager service.
   *
   * @var \Drupal\vactory_points\Services\VactoryPointsManagerInterface
   */
  protected $vactoryPointsManager;

  /**
   * Flag event subscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\vactory_points\Services\VactoryPointsManagerInterface $vactoryPointsManager
   *   Vactory points manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, VactoryPointsManagerInterface $vactoryPointsManager) {
    $this->configFactory = $configFactory;
    $this->vactoryPointsManager = $vactoryPointsManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED] = 'onFlag';
    $events[FlagEvents::ENTITY_UNFLAGGED] = 'onUnflag';
    return $events;
  }

  /**
   * On flag event handler.
   */
  public function onFlag(FlaggingEvent $event) {
    $action = 'flag';
    $config = $this->configFactory->get('vactory_points.settings');
    $flagging = $event->getFlagging();
    $entity = $flagging->getFlaggable();
    $flag_id = $flagging->getFlagId();
    $rules = $config->get('rules');
    $rules = array_filter($rules, function ($rule) use ($flag_id, $action) {
      return in_array($flag_id, $rule['action']['concerned_flags']) && $rule['action']['value'] === $action;
    });

    if (!empty($rules)) {
      $this->vactoryPointsManager->triggerUserPointsUpdate($action, $entity);
    }
  }

  /**
   * On unflag event handler.
   */
  public function onUnflag(UnflaggingEvent $event) {
    $action = 'unflag';
    $config = $this->configFactory->get('vactory_points.settings');
    $flagging = $event->getFlaggings();
    $flagging = reset($flagging);
    $entity = $flagging->getFlaggable();
    $flag_id = $flagging->getFlagId();
    $rules = $config->get('rules');
    $rules = array_filter($rules, function ($rule) use ($flag_id, $action) {
      return in_array($flag_id, $rule['action']['concerned_flags']) && $rule['action']['value'] === $action;
    });
    if (!empty($rules)) {
      $this->vactoryPointsManager->triggerUserPointsUpdate($action, $entity);
    }
  }

}
