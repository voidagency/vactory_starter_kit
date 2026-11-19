<?php

namespace Drupal\vactory_webform_typesafe\Exception;

/**
 * A safe API failure containing no credentials, request body or raw response.
 */
final class JevApiException extends \RuntimeException {

  /**
   * Constructs an error with an explicit retry policy.
   */
  public function __construct(string $message, public readonly bool $retryable = FALSE, public readonly int $retryAfter = 0) {
    parent::__construct($message);
  }

}
