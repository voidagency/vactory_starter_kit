<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Class UrlResolver
 * @package Drupal\vactory_core
 */
class UrlResolver
{

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a UrlResolver object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory)
  {
    $this->configFactory = $config_factory;
  }

  /**
   * @param string $uri
   *  The URI of the resource including the scheme.
   * @param array $options
   *  (optional) An associative array of additional URL options
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function fromUri($uri, $options = [])
  {
    $front_uri = $this->configFactory->get('system.site')->get('page.front');

    if ($front_uri === $uri) {
      return Url::fromRoute('<front>', $options)->toString();
    }

    return Url::fromUri('internal:' . $uri, $options)->toString();
  }

}
