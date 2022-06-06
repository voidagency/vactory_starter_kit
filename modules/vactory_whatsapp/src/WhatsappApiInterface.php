<?php

namespace Drupal\vactory_whatsapp;

interface WhatsappApiInterface {
  const API_BASE_URL = "https://graph.facebook.com/v14.0";

  /**
   * Initialize API params.
   *
   * @param string $to
   *   Destination phone number.
   * @return WhatsappApiInterface
   */
  public function init(string $to): WhatsappApiInterface;

  /**
   * Set template name.
   *
   * @param string $template_name
   *   The desired template name.
   * @return WhatsappApiInterface
   */
  public function setTemplateName(string $template_name): WhatsappApiInterface;

  /**
   * Set template params.
   *
   * @param array $template_params
   *   Template params in associative array please check components section on
   *   https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates#text-based
   * @return WhatsappApiInterface
   */
  public function setTemplateParams(array $template_params): WhatsappApiInterface;

  /**
   * Prepare template message case.
   *
   * @return WhatsappApiInterface
   */
  public function prepareMessageTemplate(): WhatsappApiInterface;

  /**
   * Prepare simple text message case.
   *
   * @param string $message_text
   *   Body message text.
   * @param bool $preview_url
   *   Boolean to enable/disable url preview within message body.
   * @return WhatsappApiInterface
   */
  public function prepareTextMessage(string $message_text, bool $preview_url): WhatsappApiInterface;

  /**
   * Send whatsapp message.
   *
   * @return array
   *   Whatsapp business API success response.
   */
  public function send(): array;

}