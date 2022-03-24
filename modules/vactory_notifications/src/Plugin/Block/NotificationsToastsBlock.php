<?php

namespace Drupal\vactory_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id="vactory_notifications_toasts",
 *   admin_label=@Translation("Vactory Notifications Toasts"),
 *   category=@Translation("Vactory")
 * )
 */
class NotificationsToastsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Notifications settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $notificationsConfig;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->notificationsConfig = $this->configFactory->get('vactory_notifications.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    return [
      '#theme' => 'vactory_notifications_toasts_block',
      '#content' => [
        'enable_toast' => $this->notificationsConfig->get('enable_toast'),
      ],
      '#attached' => [
        'drupalSettings' => [
          'vactory_notifications' => [
            'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
          ],
        ],
      ],
    ];
  }

}
