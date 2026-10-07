<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\vactory_webform_typesafe\Exception\JevApiException;

/**
 * Validates Jev responses before they are stored or displayed as classifications.
 */
final class JevResponseValidator {

  /**
   * Returns only documented model, answer and usage fields.
   */
  public static function validate(array $response, array $questions): array {
    $answers = $response['answers'] ?? NULL;
    if (!is_string($response['model'] ?? NULL) || trim($response['model']) === '' || strlen($response['model']) > 255 || !is_array($answers) || array_diff_key($questions, $answers) || array_diff_key($answers, $questions)) {
      self::invalid();
    }
    foreach (['input_tokens', 'output_tokens'] as $field) {
      if (!is_int($response['usage'][$field] ?? NULL) || $response['usage'][$field] < 0) {
        self::invalid();
      }
    }
    $normalized = [];
    foreach ($questions as $id => $question) {
      $answer = $answers[$id];
      if (!is_array($answer) || ($answer['type'] ?? NULL) !== $question['type']) {
        self::invalid();
      }
      if ($question['type'] === 'noul') {
        self::probability($answer['noul'] ?? NULL);
        $normalized[$id] = ['type' => 'noul', 'noul' => $answer['noul']];
        continue;
      }
      self::probability($answer['confidence'] ?? NULL);
      $probabilities = $answer['probabilities'] ?? NULL;
      $criteria = $question['criteria'];
      if (!is_array($probabilities) || array_diff_key($criteria, $probabilities) || array_diff_key($probabilities, $criteria)) {
        self::invalid();
      }
      foreach ($probabilities as $value) {
        self::probability($value);
      }
      // Accept rounding of each probability to two decimal places.
      if (abs(array_sum($probabilities) - 1) > 0.005 * count($probabilities) + 1.0E-9) {
        self::invalid();
      }
      $clean = ['type' => $question['type'], 'confidence' => $answer['confidence'], 'probabilities' => $probabilities];
      if ($question['type'] === 'choice') {
        $choice = $answer['choice'] ?? NULL;
        if (!is_string($choice) || !array_key_exists($choice, $criteria) || $probabilities[$choice] < max($probabilities)) {
          self::invalid();
        }
        $clean['choice'] = $choice;
      }
      else {
        $score = $answer['score'] ?? NULL;
        $legend = $answer['legend'] ?? NULL;
        if ((!is_float($score) && !is_int($score)) || !is_finite((float) $score) || $score < 0 || $score > count($criteria) - 1 || !is_array($legend) || array_diff_key($criteria, $legend) || array_diff_key($legend, $criteria)) {
          self::invalid();
        }
        foreach ($legend as $description) {
          if (!is_string($description)) {
            self::invalid();
          }
        }
        $clean['score'] = $score;
        $clean['legend'] = $legend;
      }
      $normalized[$id] = $clean;
    }
    return [
      'answers' => $normalized,
      'model' => $response['model'],
      'usage' => array_intersect_key($response['usage'], array_flip(['input_tokens', 'output_tokens'])),
    ];
  }

  /**
   * Requires finite numeric probabilities in the unit interval.
   */
  private static function probability(mixed $value): void {
    if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0 || $value > 1) {
      self::invalid();
    }
  }

  /**
   * Rejects invalid responses without incorporating untrusted response content.
   */
  private static function invalid(): never {
    throw new JevApiException('Jev returned an invalid classification response.', TRUE);
  }

}
