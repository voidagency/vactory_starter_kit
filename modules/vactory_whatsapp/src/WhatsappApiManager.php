<?php

namespace Drupal\vactory_whatsapp;

/**
 * Whatsapp API manager.
 */
class WhatsappApiManager implements WhatsappApiManagerInterface {

  /**
   * Whatsapp API service.
   *
   * @var \Drupal\vactory_whatsapp\WhatsappApiInterface
   */
  protected $whatsappApi;

  /**
   * Whatsapp API manager constructor.
   */
  public function __construct(WhatsappApiInterface $whatsappApi) {
    $this->whatsappApi = $whatsappApi;
  }

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
  public function sendTemplateMessage(string $to, string $template_name = NULL, array $template_params = [], string $langcode = NULL) : array{
    $api = $this->whatsappApi->init($to);
    if (!empty($template_name)) {
      $api->setTemplateName($template_name);
    }
    if (!empty($template_params)) {
      $api->setTemplateParams($template_params);
    }

    return $api->prepareMessageTemplate($langcode)->send();
  }

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
  public function sendTextMessage(string $to, string $message_text, bool $preview_url = TRUE) : array {
    $api = $this->whatsappApi->init($to);
    return $api->prepareTextMessage($message_text, $preview_url)->send();
  }

}
