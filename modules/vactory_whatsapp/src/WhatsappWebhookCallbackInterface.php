<?php

namespace Drupal\vactory_whatsapp;

/**
 * Whatsapp Webhook Callback Interface.
 */
interface WhatsappWebhookCallbackInterface {

  /**
   * Get plugin concerned whatsapp fields.
   *
   * @return array
   *   Returns array of concerned whatsapp fields.
   */
  public function getFields();

  /**
   * Get plugin label.
   *
   * @return string
   *   Returns the plugin label.
   */
  public function getLabel();

  /**
   * Whatsapp webhook callback logic.
   *
   * @param array $change
   *   The received change from facebook business API.
   */
  public function callback(array $change);

}