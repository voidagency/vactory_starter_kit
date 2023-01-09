<?php

namespace Drupal\vactory_push_notification\Plugin\PushServices;

use Drupal\vactory_push_notification\PushServiceBase;
use GuzzleHttp\Psr7\Request;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides FCM send functionality.
 *
 * @PushService(
 *   id = "FCM",
 *   title = @Translation("Send notifications to FCM."),
 * )
 */
class FCM extends PushServiceBase
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('vactory_push_notification.fcm');

        $form['fcm'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('FCM parameters'),
        ];

        $form['fcm']['fcm_key'] = [
            '#type' => 'textarea',
            '#title' => $this->t('FCM API Key'),
            '#default_value' => $config->get('fcm_key'),
            '#required' => TRUE,
        ];
        $form['fcm']['fcm_experience_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('FCM EXPO Experience ID'),
            '#default_value' => $config->get('experienceId'),
            '#required' => TRUE,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function saveForm(array &$form, FormStateInterface $form_state)
    {
        \Drupal::configFactory()->getEditable('vactory_push_notification.fcm')
            ->set('fcm_key', $form_state->getValue('fcm_key'))
            ->set('experienceId', $form_state->getValue('fcm_experience_id'))
            ->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest($data)
    {
        $config = $this->config('vactory_push_notification.fcm');
        $token = $config->get('fcm_key');
        $experienceId = $config->get('experienceId');


        $serviceUrl = "https://fcm.googleapis.com/fcm/send";
        $headers = [
            "Content-Type" => "application/json",
            'Authorization' => 'key=' . trim($token)
        ];

        $notification_payload = json_decode($data['payload'], TRUE);

        $content = json_encode([
            'to' => $data['token'],
            'priority' => "normal",
            'data' => [
                "experienceId" => $experienceId,
                "scopeKey" => $experienceId,
                "title" => $notification_payload['title'],
                "message" => $notification_payload['body'],
                "link" => [
                    "type" => "screen",
                    "param" => [
                        "foo" => "bar",
                        "service" => "FCM",
                    ],
                    "path" => "NotFound"
                ],
            ]
        ]);

        return new Request('POST', $serviceUrl, $headers, $content);
    }
}
