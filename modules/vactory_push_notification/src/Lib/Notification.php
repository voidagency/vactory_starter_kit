<?php

declare(strict_types=1);

namespace Drupal\vactory_push_notification\Lib;

class Notification
{
    /** @var SubscriptionInterface */
    private $subscription;

    /** @var null|string */
    private $payload;

    /** @var array Options : TTL, urgency, topic */
    private $options;

    /** @var array Auth details : VAPID */
    private $auth;

    public function __construct(SubscriptionInterface $subscription, ?string $payload, array $options, array $auth)
    {
        $this->subscription = $subscription;
        $this->payload = $payload;
        $this->options = $options;
        $this->auth = $auth;
    }

    public function getSubscription(): SubscriptionInterface
    {
        return $this->subscription;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getOptions(array $defaultOptions = []): array
    {
        $options = $this->options;
        // $options['TTL'] = array_key_exists('TTL', $options) ? $options['TTL'] : $defaultOptions['TTL'];
        // $options['urgency'] = array_key_exists('urgency', $options) ? $options['urgency'] : $defaultOptions['urgency'];
        // $options['topic'] = array_key_exists('topic', $options) ? $options['topic'] : $defaultOptions['topic'];

        return $options;
    }

    public function getAuth(array $defaultAuth): array
    {
        return count($this->auth) > 0 ? $this->auth : $defaultAuth;
    }
}
