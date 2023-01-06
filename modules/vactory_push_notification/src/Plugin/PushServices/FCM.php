<?php

namespace Drupal\vactory_push_notification\Plugin\PushServices;

use Drupal\vactory_push_notification\PushServiceBase;
use GuzzleHttp\Psr7\Request;

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
    public function getRequest($data)
    {
        $serviceUrl = "https://webhook.site/8aaff31b-90ac-4e5f-8425-6150a5946489";
        $headers = ['X-Service' => 'FCM'];
        $content = json_encode([
            'endpoint' => 'ANDROID',
            'token' => $data['token'],
            'apiKey' => $this->keysHelper->getFcmKey(), // todo: get from config
            'payload' => json_encode($data['payload'])
        ]);

        return new Request('POST', $serviceUrl, $headers, $content);
    }
}
