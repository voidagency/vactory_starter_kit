<?php

namespace Drupal\vactory_whatsapp;

/**
 * Whatsapp Api Manager interface.
 */
interface WhatsappApiManagerInterface {

  /**
   * Send Whatsapp template message case.
   *
   * @param string $to
   *   Destination phone number.
   * @param string|null $template_name
   *   The desired template name default to the selected template in module config.
   * @param array $template_params
   *   Template params in associative array please check components section on
   *   https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates#text-based
   * @param string|null $langcode
   *   Template langcode if exist.
   *
   * @return array
   */
  public function sendTemplateMessage(string $to, string $template_name = NULL, array $template_params = [], string $langcode = NULL) : array;

  /**
   * Send Whatsapp simple text message case.
   *
   * @param string $to
   *   Destination phone number.
   * @param string $message_text
   *   Body message text.
   * @param bool $preview_url
   *   Boolean to enable/disable url preview within message body.
   * @return array
   *   Whatsapp business API success response.
   */
  public function sendTextMessage(string $to, string $message_text, bool $preview_url) : array;
}