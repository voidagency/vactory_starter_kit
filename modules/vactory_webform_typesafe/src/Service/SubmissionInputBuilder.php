<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Builds a bounded input from explicitly selected text fields.
 */
final class SubmissionInputBuilder {

  /**
   * Only simple text-bearing fields can be sent, never files or composites. */

  /**
   * {@inheritdoc}
   */
  public function eligibleFields(WebformInterface $webform): array {
    $options = [];
    foreach ($webform->getElementsInitializedAndFlattened() as $key => $element) {
      if (in_array($element['#type'] ?? '', [
        'textfield',
        'textarea',
        'select',
        'radios',
        'checkbox',
        'checkboxes',
        'number',
        'range',
      ], TRUE)) {
        $options[$key] = strip_tags((string) ($element['#title'] ?? $key));
      }
    }
    return $options;
  }

  /**
   * Builds the selected input and rejects oversized content.
   */
  public function build(WebformSubmissionInterface $submission, array $selected, int $max_bytes): array {
    $labels = $this->eligibleFields($submission->getWebform());
    $data = $submission->getData();
    $state = [];
    foreach ($selected as $key) {
      if (!isset($labels[$key]) || !array_key_exists($key, $data)) {
        continue;
      }
      $value = $data[$key];
      if (is_array($value)) {
        $value = array_values(array_filter($value, 'is_scalar'));
      }
      if (!is_scalar($value) && !is_array($value)) {
        continue;
      }
      if ($value === [] || trim(is_array($value) ? implode(' ', $value) : (string) $value) === '') {
        continue;
      }
      $state[$key] = ['label' => $labels[$key], 'value' => $value];
    }
    if (strlen(json_encode($state, JSON_THROW_ON_ERROR)) > $max_bytes) {
      throw new \LengthException('Selected input exceeds the configured byte limit.');
    }
    return $state;
  }

}
