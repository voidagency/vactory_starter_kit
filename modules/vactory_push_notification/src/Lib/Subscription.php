<?php

declare(strict_types=1);

namespace Drupal\vactory_push_notification\Lib;

class Subscription implements SubscriptionInterface
{
    /** @var string */
    private $endpoint;

    /** @var null|string */
    private $token;

    /**
     * @param string|null
     * @throws \ErrorException
     */
    public function __construct(
        string $endpoint,
        ?string $token = null
    ) {
        $this->endpoint = $endpoint;
        $this->token = $token;
    }

    /**
     * @param array $associativeArray (with keys endpoint, token)
     * @throws \ErrorException
     */
    public static function create(array $associativeArray): self
    {
        if (array_key_exists('keys', $associativeArray) && is_array($associativeArray['keys'])) {
            return new self(
                $associativeArray['endpoint'],
            );
        }

        if (array_key_exists('token', $associativeArray)) {
            return new self(
                $associativeArray['endpoint'],
                $associativeArray['token'] ?? null,
            );
        }

        return new self(
            $associativeArray['endpoint']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
}
