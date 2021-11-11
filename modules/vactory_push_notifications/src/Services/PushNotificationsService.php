<?php


namespace Drupal\vactory_push_notifications\Services;

use DateTime;
use Drupal\block\Entity\Block;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class PushNotificationsService.
 */
class PushNotificationsService
{

  use StringTranslationTrait;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new PushNotificationsService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger)
  {
    $this->logger = $logger;
  }

  /**
   * Function to disable or enable push notifications block.
   */
  public function updatePushNotificationsStatus()
  {
    $blocks = Block::loadMultiple();

    foreach ($blocks as &$block) {
      if ($block->getPluginId() == 'vactory_push_notifications_block') {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
        $block_plugin = $block->getPlugin();
        $config = $block_plugin->getConfiguration();

        // Get dates values.
        $today = new DateTime();
        $begin_date = DateTime::createFromFormat('Y-m-d', $config['begin_date']);
        $end_date = DateTime::createFromFormat('Y-m-d', $config['end_date']);

        // Get dates diff.
        $interval_begin = $begin_date->diff($today);
        $interval_end = $today->diff($end_date);

        // Check if the block should be disabled or enabled.
        if ($block->status() && ($interval_begin->invert || $interval_end->invert)) {
          $block->disable();
          $block->save();
          $this->logger->get("vactory_push_notifications")->info("PushNotifications désactivé.");
        } elseif (!$block->status() && !($interval_begin->invert || $interval_end->invert)) {
          $block->enable();
          $block->save();
          $this->logger->get("vactory_push_notifications")->info("PushNotifications activé.");
        }
      }
    }
  }

}
