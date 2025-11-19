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
    $anonymized_roles = $settings['roles'] ?? [];
    $user_roles = $this->currentUser->getRoles();

    return !empty($settings['enabled'])
      && !empty($anonymized_roles)
      && !empty(array_intersect($user_roles, $anonymized_roles));
  }

  /**
   * Recursively anonymize values in nested arrays.
   */
  public function anonymizeRecursive($value, array $settings = [], $view_mode = 'html') {
    if (is_array($value)) {
      return $this->anonymizeArray($value, $settings, $view_mode);
    }

    return $this->anonymizeScalarValue($value, $settings);
  }

  /**
   * Anonymize array values.
   */
  protected function anonymizeArray(array $value, array $settings, $view_mode) {
    // Early return for table headers.
    if ($view_mode === 'table' && in_array('header', $value)) {
      return $value;
    }

    // Handle special array keys.
    $special_key_result = $this->handleSpecialArrayKeys($value, $settings);
    if ($special_key_result !== NULL) {
      return $special_key_result;
    }

    // Handle #children array (for multi-page webforms).
    if (isset($value['#children']) && is_array($value['#children'])) {
      foreach ($value['#children'] as &$child_value) {
        $child_value = $this->anonymizeRecursive($child_value, $settings, $view_mode);
      }
    }

    // Process array items.
    foreach ($value as $key => &$item) {
      if ($this->shouldSkipKey($key)) {
        continue;
      }
      $item = $this->anonymizeRecursive($item, $settings, $view_mode);
    }

    return $value;
  }

  /**
   * Handle special array keys that need special processing.
   */
  protected function handleSpecialArrayKeys(array $value, array $settings) {
    $result = NULL;

    if (isset($value['#file'])) {
      $result = $this->anonymizeValue($value['#file']->getFilename() ?? "", $settings);
    }
    elseif (isset($value['#markup'])) {
      $value['#markup'] = $this->anonymizeValue($value['#markup'], $settings);
      $result = $value;
    }
    elseif (isset($value['#url']) && $value['#url'] instanceof Url && isset($value['#title'])) {
      $result = $this->anonymizeValue($value['#title'], $settings);
    }

    return $result;
  }

  /**
   * Check if a key should be skipped during anonymization.
   */
  protected function shouldSkipKey($key) {
    if (!is_string($key) || strpos($key, '#') !== 0) {
      return FALSE;
    }

    $skip_keys = [
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
    ];

    return in_array($key, $skip_keys);
  }

  /**
   * Anonymize scalar values (strings, objects, etc.).
   */
  protected function anonymizeScalarValue($value, array $settings) {
    $string_value = $this->convertToAnonymizableString($value);
    if ($string_value !== NULL) {
      return $this->anonymizeValue($string_value, $settings);
    }

    return $value;
  }

  /**
   * Convert value to string if it can be anonymized.
   */
  protected function convertToAnonymizableString($value) {
    if (is_string($value)) {
      return $value;
    }

    if (!is_object($value)) {
      return NULL;
    }

    $result = NULL;
    if ($value instanceof Link || $value instanceof Url) {
      $result = (string) $value->toString();
    }
    elseif (method_exists($value, '__toString')) {
      $result = (string) $value;
    }

    return $result;
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

    return $this->applyAnonymizationMode($trimmed, $length, $mode, $custom_chars, $mask_char);
  }

  /**
   * Apply anonymization based on the selected mode.
   */
  protected function applyAnonymizationMode($trimmed, $length, $mode, $custom_chars, $mask_char) {
    $visible_length = $this->getVisibleLength($length, $mode, $custom_chars);

    if ($visible_length >= $length) {
      return str_repeat($mask_char, $length);
    }

    $visible_part = mb_substr($trimmed, 0, $visible_length);
    $masked_part = str_repeat($mask_char, $length - $visible_length);
    return $visible_part . $masked_part;
  }

  /**
   * Get the visible length based on anonymization mode.
   */
  protected function getVisibleLength($length, $mode, $custom_chars) {
    $visible_length = 0;

    switch ($mode) {
      case 'full':
        $visible_length = 0;
        break;

      case 'partial':
        $visible_length = min(2, $length);
        break;

      case 'custom':
        $visible_length = min($custom_chars, $length);
        break;

      case 'half':
      default:
        $visible_length = ceil($length / 2);
        break;
    }

    return $visible_length;
  }

}
