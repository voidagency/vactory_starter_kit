<?php

namespace Drupal\vactory_push_notification\Plugin\PushServices;

use Drupal\vactory_push_notification\PushServiceBase;
use GuzzleHttp\Psr7\Request;

/**
 * Provides APN send functionality.
 *
 * @PushService(
 *   id = "APN",
 *   title = @Translation("Send notifications to APN."),
 * )
 */
class APN extends PushServiceBase
{
    /**
     * {@inheritdoc}
     */
    public function getRequest($data)
    {
        $serviceUrl = "https://webhook.site/8aaff31b-90ac-4e5f-8425-6150a5946489";
        $headers = ['X-Service' => 'APN'];
        $content = json_encode([
            'endpoint' => 'IOS',
            'token' => $data['token'],
            'apiKey' => $this->keysHelper->getApnKey(),
            'payload' => json_encode($data['payload'])
        ]);

        return new Request('POST', $serviceUrl, $headers, $content);
    }
}
