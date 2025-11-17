<?php

namespace Drupal\vactory_webform_anonymize\Services;

use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;

/**
 * The Anonymized Webform Submission Service.
 */
class AnonymizedWebformSubmissionService {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  public $currentUser;

  /**
   * The anonymize webform submission service construct.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The currently logged-in user.
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * Should anonymize.
   */
  public function shouldAnonymize(WebformInterface $webform) {
    if ($this->currentUser->hasPermission('administer webform')) {
      return FALSE;
    }

    $settings = $webform->getThirdPartySetting('vactory_webform_anonymize', 'settings', []);

    if (empty($settings['enabled'])) {
      return FALSE;
    }

    $anonymized_roles = $settings['roles'] ?? [];
    if (empty($anonymized_roles)) {
      return FALSE;
    }

    $user_roles = $this->currentUser->getRoles();
    return !empty(array_intersect($user_roles, $anonymized_roles));
  }

  /**
   * Recursively anonymize values in nested arrays.
   */
  public function anonymizeRecursive($value, array $settings = [], $view_mode = 'html') {
    if (is_array($value)) {
      if ($view_mode === 'table' && in_array('header', $value)) {
        return $value;
      }

      if (isset($value['#file'])) {
        return $this->anonymizeValue($value['#file']->getFilename() ?? "", $settings);
      }

      if (isset($value['#markup'])) {
        $value['#markup'] = $this->anonymizeValue($value['#markup'], $settings);
        return $value;
      }

      if (isset($value['#url']) && $value['#url'] instanceof Url) {
        return $this->anonymizeValue($value['#title'], $settings);
      }

      // Handle #children array (for multi-page webforms).
      if (isset($value['#children']) && is_array($value['#children'])) {
        foreach ($value['#children'] as &$child_value) {
          $child_value = $this->anonymizeRecursive($child_value, $settings, $view_mode);
        }
      }

      foreach ($value as $key => &$item) {
        if (is_string($key) && strpos($key, '#') === 0) {
          if (in_array($key, [
            '#text',
            '#children',
            '#theme',
            '#type',
            '#weight',
            '#prefix',
            '#suffix',
            '#attributes',
            '#webform_submission',
            '#options',
            '#element',
            '#id',
            '#open',
            '#title',
          ])) {
            continue;
          }
        }
        $item = $this->anonymizeRecursive($item, $settings, $view_mode);
      }
      return $value;
    }

    if (is_string($value)) {
      return $this->anonymizeValue($value, $settings);
    }

    if (is_object($value) && method_exists($value, '__toString')) {
      return $this->anonymizeValue((string) $value, $settings);
    }

    if ($value instanceof Link) {
      return $this->anonymizeValue((string) $value->toString(), $settings);
    }

    if ($value instanceof Url) {
      return $this->anonymizeValue((string) $value->toString(), $settings);
    }

    return $value;
  }

  /**
   * Anonymize a value based on settings.
   */
  public function anonymizeValue($value, array $settings = []) {
    if (empty($value) || !is_string($value)) {
      return $value;
    }

    // Get settings with defaults.
    $mode = $settings['anonymize_mode'] ?? 'half';
    $custom_chars = $settings['custom_chars'] ?? 3;
    $mask_char = $settings['mask_character'] ?? '*';

    // Strip HTML tags for processing.
    $stripped = strip_tags($value);
    $trimmed = trim($stripped);
    $length = mb_strlen($trimmed);

    // If value is empty after stripping.
    if ($length === 0) {
      return $value;
    }

    switch ($mode) {
      case 'full':
        return str_repeat($mask_char, $length);

      case 'partial':
        if ($length <= 2) {
          return str_repeat($mask_char, $length);
        }
        $visible_part = mb_substr($trimmed, 0, 2);
        $masked_part = str_repeat($mask_char, $length - 2);
        return $visible_part . $masked_part;

      case 'custom':
        if ($length <= $custom_chars) {
          return str_repeat($mask_char, $length);
        }
        $visible_part = mb_substr($trimmed, 0, $custom_chars);
        $masked_part = str_repeat($mask_char, $length - $custom_chars);
        return $visible_part . $masked_part;

      case 'half':
      default:
        if ($length <= 2) {
          return str_repeat($mask_char, $length);
        }
        $half = ceil($length / 2);
        $visible_part = mb_substr($trimmed, 0, $half);
        $masked_part = str_repeat($mask_char, $length - $half);
        return $visible_part . $masked_part;
    }
  }

}
