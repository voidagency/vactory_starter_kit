<?php

declare(strict_types=1);

namespace Drupal\vactory_push_notification\Lib;

interface SubscriptionInterface
{
    public function getEndpoint(): string;

    public function getToken(): ?string;
}
