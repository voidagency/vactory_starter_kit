<?php
namespace Drupal\vactory_whatsapp;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Whatsapp Webhook Manager.
 */
class WhatsappWebhookManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/WhatsappWebhookCallback',
      $namespaces,
      $module_handler,
      'Drupal\vactory_whatsapp\WhatsappWebhookCallbackInterface',
      'Drupal\vactory_whatsapp\Annotation\WhatsappWebhookCallback'
    );
    $this->setCacheBackend($cache_backend, 'whatsapp_webhook_info_plugins');
  }

  /**
   * Get whatsapp webhook callback plugin definitions by field.
   */
  public function getDefinitionsByField($field) {
    $definitions = $this->getDefinitions();
    $definitions = array_filter($definitions, function($definition) use ($field) {
      return in_array($field, $definition['fields']);
    });
    return $definitions;
  }

}
