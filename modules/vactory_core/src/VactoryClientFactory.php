<?php

namespace Drupal\vactory_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\HandlerStack;

/**
 * Helper class to construct a HTTP client with Vactory specific config.
 */
class VactoryClientFactory extends ClientFactory {

  /**
   * Client factory (original service).
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected ClientFactory $clientFactory;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ClientFactory instance.
   */
  public function __construct(ClientFactory $clientFactory, HandlerStack $stack, ConfigFactoryInterface $configFactory) {
    $this->clientFactory = $clientFactory;
    $this->configFactory = $configFactory;
    parent::__construct(
      $stack
    );
  }

  /**
   * Constructs a new client object from some configuration.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function fromOptions(array $config = []) {
    // SSL verification stays on unless it has been explicitly disabled in
    // the Vactory settings. A "verify" option passed by the caller wins.
    $ssl_verification = $this->configFactory->get('vactory_core.global_config')->get('http_client_ssl_verification');
    if ($ssl_verification !== NULL && !$ssl_verification && !array_key_exists('verify', $config)) {
      $config['verify'] = FALSE;
    }
    return $this->clientFactory->fromOptions($config);
  }

}
