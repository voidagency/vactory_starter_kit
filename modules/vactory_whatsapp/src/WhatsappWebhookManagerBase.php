<?php

namespace Drupal\vactory_whatsapp;

use Drupal\Core\Plugin\PluginBase;

/**
 * Whatsapp Webhook Manager Base.
 */
abstract class WhatsappWebhookManagerBase extends PluginBase implements \Drupal\vactory_whatsapp\WhatsappWebhookCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public function getFields() {
    return $this->pluginDefinition['fields'];
  }

  /**
   * {@inheritDoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

}
