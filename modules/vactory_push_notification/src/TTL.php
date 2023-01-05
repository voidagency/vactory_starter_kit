<?php

namespace Drupal\vactory_push_notification;

/**
 * Push notification Time-To-Live.
 */
class TTL {

  /**
   * TTL pattern.
   * Accepts plain number value it's treated as minutes.
   * Suffix 'h' is treated as hours.
   * Suffix 'd' is treated as days.
   */
  const PATTERN = '/^([0-9]+)([hd]{1})?$/';

  /**
   * Validates a TTL value.
   *
   * @param string $value
   *   The TTL value.
   *
   * @return bool
   */
  public function validate($value) {
    return preg_match(self::PATTERN, $value);
  }

  /**
   * Converts a TTL value to minutes.
   *
   * @param string $value
   *   The TTL value.
   *
   * @return int
   *   Minutes.
   *
   * @throws \Drupal\vactory_push_notification\InvalidTTLException
   */
  public function toMinutes($value) {
    if (!$this->validate($value)) {
      throw new InvalidTTLException($value);
    }

    preg_match(self::PATTERN, $value, $matches);
    if (count($matches) === 2) {
      return (int) $value;
    }

    $minutes = 0;

    switch ($matches[2]) {
      case 'h':
        $minutes = ((int) $matches[1]) * 60;
        break;

      case 'd':
        $minutes = ((int) $matches[1]) * 60 * 24;
        break;
    }

    return $minutes;
  }

}
