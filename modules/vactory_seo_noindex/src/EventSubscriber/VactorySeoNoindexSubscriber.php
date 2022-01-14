<?php

namespace Drupal\vactory_seo_noindex\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * vactory_seo_noindex event subscriber.
 */
class VactorySeoNoindexSubscriber implements EventSubscriberInterface {

  /**
   * Config Factory sevrice
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current path stack service
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs event subscriber.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    CurrentPathStack $currentPathStack,
    AliasManagerInterface $aliasManager
  ) {
    $this->configFactory = $configFactory;
    $this->currentPathStack = $currentPathStack;
    $this->aliasManager = $aliasManager;
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    $config = $this->configFactory->get('vactory_seo_noindex.settings');
    $paths = $config->get('artifact_json');
    if (!empty($paths)) {
      $paths = Json::decode($paths);
      if (!empty($paths) && is_array($paths)) {
        $current_path = $this->currentPathStack->getPath();
        $current_path_alias = $this->aliasManager->getAliasByPath($current_path);
        if (in_array($current_path_alias, $paths) || in_array($current_path, $paths)) {
          $response = $event->getResponse();
          $response->headers->set('X-Robots-Tag', 'noindex');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    return $events;
  }

}
