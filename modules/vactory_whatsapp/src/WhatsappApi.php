<?php

namespace Drupal\vactory_whatsapp;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vactory_whatsapp\Exceptions\WhatsappApiException;
use GuzzleHttp\Client;

/**
 * Whatsapp API.
 */
class WhatsappApi implements WhatsappApiInterface {

  /**
   * Whatsapp API endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Whatsapp business account access token.
   *
   * @var string
   */
  protected $permanentToken;

  /**
   * Whatsapp business account phone number id.
   *
   * @var string
   */
  protected $phoneNumId;

  /**
   * Message template name
   *
   * @var string
   */
  protected $templateName;

  /**
   * Message template params.
   *
   * @var array
   */
  protected $templateParams;

  /**
   * Whatsapp message object.
   *
   * @var array
   */
  protected $messageObject;

  /**
   * Http client service.
   *
   * @var Client
   */
  protected $httpClient;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Whatsapp API constructor.
   *
   * @param Client $httpClient
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(Client $httpClient, ConfigFactoryInterface $configFactory)
  {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Initialize API params.
   */
  public function init(string $to): WhatsappApiInterface
  {
    $config = $this->configFactory->get('vactory_whatsapp.settings');
    $this->templateName = $config->get('template_id');
    $phone_num_id = $this->phoneNumId = $config->get('phone_num_id');
    $this->permanentToken = $config->get('token');
    if (empty($phone_num_id) || empty($this->permanentToken)) {
      throw new WhatsappApiException('Module vactory_whatsapp has not been configured yet');
    }
    $whatsapp_api_url = WhatsappApiInterface::API_BASE_URL;
    $this->endpoint = "${whatsapp_api_url}/${phone_num_id}/messages";
    $this->templateParams = [];
    $this->messageObject = [
      'messaging_product' => 'whatsapp',
      'to' => $to,
    ];
    return $this;
  }

  /**
   * Set template name.
   */
  public function setTemplateName(string $template_name): WhatsappApiInterface
  {
    $this->templateName = $template_name;
    return $this;
  }

  /**
   * Set template params.
   */
  public function setTemplateParams(array $template_params): WhatsappApiInterface
  {
    $this->templateParams = $template_params;
    return $this;
  }

  /**
   * Prepare template message case.
   */
  public function prepareMessageTemplate($langcode = NULL): WhatsappApiInterface
  {
    if (!isset($this->messageObject['messaging_product'])) {
      throw new WhatsappApiException("Method init() has not been executed yet");
    }
    $this->messageObject['type'] = 'template';
    $this->messageObject['template'] = [
      'name' => $this->templateName,
    ];
    if (!empty($langcode)) {
      $this->messageObject['template']['language']['code'] = $langcode;
    }
    if (!empty($this->templateParams)) {
      $this->messageObject['template']['components'] = $this->templateParams;
    }
    return $this;
  }

  /**
   * Prepare simple text message case.
   */
  public function prepareTextMessage(string $message_text, bool $preview_url = TRUE): WhatsappApiInterface
  {
    if (!isset($this->messageObject['messaging_product'])) {
      throw new WhatsappApiException("Method init() has not been executed yet");
    }
    $this->messageObject['recipient_type'] = 'individual';
    $this->messageObject['type'] = 'text';
    $this->messageObject['text'] = [
      'preview_url' => $preview_url,
      'body' => $message_text,
    ];

    return $this;
  }

  /**
   * Send whatsapp message.
   */
  public function send(): array {
    if (empty($this->endpoint) || empty($this->permanentToken)) {
      throw new WhatsappApiException("Method init() has not been executed yet");
    }
    $options = [
      'json' => $this->messageObject,
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $this->permanentToken,
      ],
    ];
    $response = $this->httpClient->post($this->endpoint, $options)
      ->getBody()
      ->getContents();
    $response = Json::decode($response);
    if (isset($response['error'])) {
      $error = $response['error']['message'];
      $error .= isset($response['error']['error_data']['details']) ? PHP_EOL . $response['error']['error_data']['details'] : '';
      throw new WhatsappApiException($error);
    }
    return $response;
  }
}
