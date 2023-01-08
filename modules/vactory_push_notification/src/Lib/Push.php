<?php

declare(strict_types=1);

namespace Drupal\vactory_push_notification\Lib;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
// use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class Push
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var null|array Array of array of Notifications
     */
    protected $notifications;

    /**
     * @var array Default options : TTL, urgency, topic, batchSize
     */
    protected $defaultOptions;

    /**
     * @var int Automatic padding of payloads, if disabled, trade security for bandwidth
     */
    protected $automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;

    /**
     * Push Service Plugins
     * 
     * @var \Drupal\vactory_push_notification\PushServiceInterface[]
     */
    protected $plugins = [];

    /**
     * WebPush constructor.
     *
     * @param array    $plugins           push service plugins
     * @param array    $defaultOptions TTL, urgency, topic, batchSize
     * @param int|null $timeout        Timeout of POST request
     *
     * @throws \ErrorException
     */
    public function __construct(array $plugins = [], array $defaultOptions = [], ?int $timeout = 30, array $clientOptions = [])
    {
        $extensions = [
            'curl' => '[WebPush] curl extension is not loaded but is required. You can fix this in your php.ini.',
            'mbstring' => '[WebPush] mbstring extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.',
            'openssl' => '[WebPush] openssl extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.',
        ];
        $phpVersion = phpversion();
        if ($phpVersion && version_compare($phpVersion, '7.3.0', '<')) {
            $extensions['gmp'] = '[WebPush] gmp extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.';
        }
        foreach ($extensions as $extension => $message) {
            if (!extension_loaded($extension)) {
                trigger_error($message, E_USER_WARNING);
            }
        }

        if (ini_get('mbstring.func_overload') >= 2) {
            trigger_error("[WebPush] mbstring.func_overload is enabled for str* functions. You must disable it if you want to send push notifications with payload or use VAPID. You can fix this in your php.ini.", E_USER_NOTICE);
        }

        $this->plugins = $plugins;

        $this->setDefaultOptions($defaultOptions);

        if (!array_key_exists('timeout', $clientOptions) && isset($timeout)) {
            $clientOptions['timeout'] = $timeout;
        }
        $this->client = new Client($clientOptions);
    }

    /**
     * Queue a notification. Will be sent when flush() is called.
     *
     * @param string|null $payload If you want to send an array or object, json_encode it
     * @param array $options Array with several options tied to this notification. If not set, will use the default options that you can set in the WebPush object
     * @param array $auth Use this auth details instead of what you provided when creating WebPush
     * @throws \ErrorException
     */
    public function queueNotification(SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []): void
    {
        if (isset($payload)) {
            if (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH) {
                throw new \ErrorException('Size of payload must not be greater than ' . Encryption::MAX_PAYLOAD_LENGTH . ' octets.');
            }
        }

        $this->notifications[] = new Notification($subscription, $payload, $options, $auth);
    }

    /**
     * @param string|null $payload If you want to send an array or object, json_encode it
     * @param array $options Array with several options tied to this notification. If not set, will use the default options that you can set in the WebPush object
     * @param array $auth Use this auth details instead of what you provided when creating WebPush
     * @throws \ErrorException
     */
    public function sendOneNotification(SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []): MessageSentReport
    {
        $this->queueNotification($subscription, $payload, $options, $auth);
        return $this->flush()->current();
    }

    /**
     * Flush notifications. Triggers the requests.
     *
     * @param null|int $batchSize Defaults the value defined in defaultOptions during instantiation (which defaults to 1000).
     *
     * @return \Generator|MessageSentReport[]
     * @throws \ErrorException
     */
    public function flush(?int $batchSize = null): \Generator
    {
        if (empty($this->notifications)) {
            yield from [];
            return;
        }

        if (null === $batchSize) {
            $batchSize = $this->defaultOptions['batchSize'];
        }

        $batches = array_chunk($this->notifications, $batchSize);

        // reset queue
        $this->notifications = [];

        foreach ($batches as $batch) {
            // for each endpoint server type
            $requests = [];
            try {
                $requests = $this->prepare($batch);
            } catch (\Exception $e) {
                $requests = [];
                dpm($e);
            }

            $promises = [];



            foreach ($requests as $request) {
                $promises[] = $this->client->sendAsync($request)
                    ->then(function ($response) use ($request) {
                        /** @var ResponseInterface $response * */
                        return new MessageSentReport($request, $response);
                    })
                    ->otherwise(function ($reason) {
                        /** @var RequestException $reason **/
                        if (method_exists($reason, 'getResponse')) {
                            $response = $reason->getResponse();
                        } else {
                            $response = null;
                        }
                        return new MessageSentReport($reason->getRequest(), $response, false, $reason->getMessage());
                    });
            }

            foreach ($promises as $promise) {
                $d = $promise->wait();
                // dpm($d);
                yield $d;
            }
        }
    }

    /**
     * @throws \ErrorException
     */
    protected function prepare(array $notifications): array
    {
        $requests = [];
        foreach ($notifications as $notification) {
            \assert($notification instanceof Notification);
            $subscription = $notification->getSubscription();
            $endpoint = $subscription->getEndpoint();
            $token = $subscription->getToken();
            $payload = $notification->getPayload();

            // todo: endpoint should be plugin_id
            $plugin_id = $endpoint === "ios" ? "APN" : "FCM"; // todo: no need for this check
            $plugin = $this->plugins[$plugin_id];

            $requests[] = $plugin->getRequest([
                'token' => $token,
                'payload' => $payload
            ]);

            // try {
            //     $requests[] = $plugin->getRequest([
            //         'token' => $token,
            //         'payload' => $payload
            //     ]);
            // } catch (\Exception $e) {
            //     dpm($e); // todo: remove
            // }
        }

        return $requests;
    }

    public function isAutomaticPadding(): bool
    {
        return $this->automaticPadding !== 0;
    }

    /**
     * @return int
     */
    public function getAutomaticPadding()
    {
        return $this->automaticPadding;
    }

    /**
     * @param int|bool $automaticPadding Max padding length
     *
     * @throws \Exception
     */
    public function setAutomaticPadding($automaticPadding): Push
    {
        if ($automaticPadding > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \Exception('Automatic padding is too large. Max is ' . Encryption::MAX_PAYLOAD_LENGTH . '. Recommended max is ' . Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH . ' for compatibility reasons (see README).');
        } elseif ($automaticPadding < 0) {
            throw new \Exception('Padding length should be positive or zero.');
        } elseif ($automaticPadding === true) {
            $this->automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;
        } elseif ($automaticPadding === false) {
            $this->automaticPadding = 0;
        } else {
            $this->automaticPadding = $automaticPadding;
        }

        return $this;
    }

    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * @param array $defaultOptions Keys 'TTL' (Time To Live, defaults 4 weeks), 'urgency', 'topic', 'batchSize'
     *
     * @return WebPush
     */
    public function setDefaultOptions(array $defaultOptions)
    {
        // $this->defaultOptions['TTL'] = $defaultOptions['TTL'] ?? 2419200;
        // $this->defaultOptions['urgency'] = $defaultOptions['urgency'] ?? null;
        // $this->defaultOptions['topic'] = $defaultOptions['topic'] ?? null;
        $this->defaultOptions['batchSize'] = $defaultOptions['batchSize'] ?? 1000;

        return $this;
    }

    public function countPendingNotifications(): int
    {
        return null !== $this->notifications ? count($this->notifications) : 0;
    }
}
