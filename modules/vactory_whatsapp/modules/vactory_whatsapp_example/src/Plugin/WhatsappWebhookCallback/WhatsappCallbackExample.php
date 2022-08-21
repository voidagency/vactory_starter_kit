<?php

namespace Drupal\vactory_whatsapp_example\Plugin\WhatsappWebhookCallback;

use Drupal\user\UserInterface;
use Drupal\vactory_whatsapp\WhatsappApiManager;
use Drupal\vactory_whatsapp\WhatsappWebhookManagerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

/**
 * @WhatsappWebhookCallback(
 *   id="vactory_whatsapp_callback_example",
 *   fields={
 *     "messages",
 *   },
 *   label=@Translation("Whtassap Webhook Example")
 * )
 */
class WhatsappCallbackExample extends WhatsappWebhookManagerBase {

  /**
   * {@inheritDoc}
   */
  public function callback(array $change) {
    if (isset($change['value']['messages'])) {
      $message = $change['value']['messages'][0]['text']['body'] ?? NULL;
      $phone_id = $change['value']['metadata']['phone_number_id'] ?? NULL;
      $phone_number = $change['value']['messages'][0]['from'] ?? NULL;
      $name = $change['value']['contacts'][0]['profile']['name'] ?? 'Whatsapp user';
      $email = $phone_number ? "$phone_number@whatsapp.dv" : "user@whatsapp.dv";
      /** @var WhatsappApiManager $whatsapp_manager */
      $whatsapp_manager = \Drupal::service('vactory_whatsapp.api.manager');
      $config = \Drupal::state()->get($phone_id);
      $isInteractif = $config['isInteractif'] ?? FALSE;
      $isContactAnswer = $config['isContactAnswer'] ?? FALSE;
      switch ($message) {
        case strtolower(trim($message, '*')) === "activer" && !$isContactAnswer:
          $config = [
            'isInteractif' => TRUE,
            'isContactAnswer' => FALSE,
          ];
          \Drupal::state()->set($phone_id, $config);
          $whatsapp_manager->sendTemplateMessage($phone_number, 'menu_list', [], 'fr');
          break;
        case strtolower(trim($message, '*')) === "news" && !$isContactAnswer:
          if ($isInteractif) {
            $whatsapp_manager->sendTemplateMessage($phone_number, 'news_listing', [], 'fr');
          }
          break;
        case strtolower(trim($message, '*')) === "account" && !$isContactAnswer:
          if ($isInteractif && $phone_number) {
            $phone_number_a = $phone_number;
            $phone_number_b = $phone_number;
            if (strpos($phone_number, '212') === 0) {
              $phone_number_b = "+$phone_number";
              $num = substr_replace($phone_number, '', 0, 3);
              $phone_number_a = "0$num";
            }
            $phones = [
              $phone_number,
              $phone_number_b,
              $phone_number_a
            ];
            $user = \Drupal::entityTypeManager()->getStorage('user')
              ->loadByProperties(['field_telephone' => $phones]);
            if ($user) {
              /** @var UserInterface $user */
              $user = reset($user);
              $blocked = $user->isBlocked();
              $status = [
                [
                  'type' => 'body',
                  'parameters' => [
                    [
                      'type' => 'text',
                      'text' => 'Activé',
                    ],
                  ],
                ],
              ];
              if (!$blocked) {
                $whatsapp_manager->sendTemplateMessage($phone_number, 'account_check', $status, 'fr');
              }
              if ($blocked) {
                $status[0]['parameters'][0]['text'] = 'Bloqué';
                $whatsapp_manager->sendTemplateMessage($phone_number, 'account_check', $status, 'fr');
              }
            } else {
              $whatsapp_manager->sendTemplateMessage($phone_number, 'account_missed', [], 'fr');
            }
          }
          break;
        case strtolower(trim($message, '*')) === "contact":
          if ($isInteractif) {
            if (!$isContactAnswer) {
              $whatsapp_manager->sendTemplateMessage($phone_number, 'contact_message', [], 'fr');
              $config = [
                'isInteractif' => TRUE,
                'isContactAnswer' => TRUE,
              ];
              \Drupal::state()->set($phone_id, $config);
            }
          }
          break;
        case strtolower(trim($message, '*')) === "menu":
          if ($isInteractif) {
            $whatsapp_manager->sendTemplateMessage($phone_number, 'menu_list', [], 'fr');
          }
          break;
        case strtolower(trim($message, '*')) === "quitter":
          $config = [
            'isInteractif' => FALSE,
            'isContactAnswer' => FALSE,
          ];
          \Drupal::state()->set($phone_id, $config);
          break;
        case !empty($message) && $isInteractif:
          if (!$isContactAnswer) {
            $whatsapp_manager->sendTemplateMessage($phone_number, 'default_enable', [], 'fr');
          }
          if ($isContactAnswer) {
            $this->createWebformSubmission($phone_number, $name, $email, $message, $whatsapp_manager);
            $config = [
              'isInteractif' => TRUE,
              'isContactAnswer' => FALSE,
            ];
            \Drupal::state()->set($phone_id, $config);
          }
          break;
      }
    }
  }

  public function createWebformSubmission($phone_number, $name, $email, $message, WhatsappApiManager $whatsapp_manager) {
    $values = [
      'webform_id' => 'contact',
      'in_draft' => FALSE,
      'uid' => '0',
      'langcode' => 'fr',
      'data' => [
        'name' => $name,
        'email' => $email,
        'subject' => 'Message from whatsapp',
        'message' => $message,
      ],
    ];
    $webform = Webform::load('contact');
    $is_open = WebformSubmissionForm::isOpen($webform);
    if ($is_open) {
      // Submit values and get submission ID.
      $webform_submission = WebformSubmissionForm::submitFormValues($values);
      $recieved_msg = [
        [
          'type' => 'body',
          'parameters' => [
            [
              'type' => 'text',
              'text' => $message,
            ],
          ],
        ],
      ];
      $whatsapp_manager->sendTemplateMessage($phone_number, 'contact_confirmation', $recieved_msg, 'fr');
    }
    else {
      $message = 'Oups! je suis vraiment désolé car les soumissions de formulaire de contact sont clôturées';
      $whatsapp_manager->sendTextMessage($phone_number, $message);
      $whatsapp_manager->sendTemplateMessage($phone_number, 'menu', [], 'fr');
    }
  }

}
