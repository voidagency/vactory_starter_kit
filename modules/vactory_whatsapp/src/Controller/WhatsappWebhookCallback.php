<?php

namespace Drupal\vactory_whatsapp\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\vactory_whatsapp\WhatsappWebhookManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vactory whatsapp webhook callback.
 */
class WhatsappWebhookCallback extends ControllerBase {

  /**
   * Builds the response.
   */
  public function handler(Request $request) {
    $config = \Drupal::config('vactory_whatsapp.settings');
    $state = \Drupal::state();
    $verify_token = $state->get('vactory_whatsapp_webhook_token', 'vactory');
    $mode = $request->query->get('hub_mode');
    $token = $request->query->get('hub_verify_token');
    $challenge = $request->query->get('hub_challenge');
    $content = $request->getContent();
    $whatsapp_webhook_plugins = $config->get('whatsapp_webhook_plugins');
    if (!empty($content)) {
      $content = Json::decode($content);
      if (isset($content['object']) && isset($content['entry']) && $content['object'] === 'whatsapp_business_account') {
        $changes = $content['entry'][0]['changes'];
        foreach ($changes as $change) {
          $field = $change['field'];
          /** @var WhatsappWebhookManager $whatsapp_webhook_manager */
          $whatsapp_webhook_manager = \Drupal::service('plugin.manager.vactory_whatsapp_webhook');
          $definitions = $whatsapp_webhook_manager->getDefinitionsByField($field);
          foreach ($definitions as $definition) {
            $enabled = $whatsapp_webhook_plugins[$definition['id']]['enable'] ?? FALSE;
            if ($enabled && $whatsapp_webhook_plugins[$definition['id']]['enable'] && isset($definition['class']) && class_exists($definition['class'])) {
              $instance = $whatsapp_webhook_manager->createInstance($definition['id']);
              $instance->callback($change);
            }
          }
        }
        return new Response('', 200);
      }
    }
    if ($verify_token === $token) {
      return new Response($challenge, 200);
    }
    return new JsonResponse('', 403);
  }

  public function content() {
    // Example of whatsapp business response for message field.
    return [
      'object' => 'whatsapp_business_account',
      'entry' => [
        0 => [
          'id' => '0',
          'changes' => [
            0 => [
              'field' => 'messages',
              'value' => [
                'messaging_product' => 'whatsapp',
                'metadata' => [
                  'display_phone_number' => '16505551111',
                  'phone_number_id' => '123456123',
                ],
                'contacts' => [
                  0 => [
                    'profile' => [
                      'name' => 'test user name',
                    ],
                    'wa_id' => '16315551181',
                  ],
                ],
                'messages' => [
                  0 => [
                    'from' => '16315551181',
                    'id' => 'ABGGFlA5Fpa',
                    'timestamp' => '1504902988',
                    'type' => 'text',
                    'text' => [
                      'body' => 'this is a text message',
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
