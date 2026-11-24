<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\vactory_webform_typesafe\Exception\JevApiException;

/**
 * Validates Jev responses before they are stored or displayed.
 */
final class JevResponseValidator {

  /**
   * Returns only documented model, answer and usage fields.
   */
  public static function validate(array $response, array $questions): array {
    $answers = $response['answers'] ?? NULL;
    self::assertEnvelope($response, $answers, $questions);
    $normalized = [];
    foreach ($questions as $id => $question) {
      $normalized[$id] = self::normalizeAnswer($answers[$id], $question);
    }
    return [
      'answers' => $normalized,
      'model' => $response['model'],
      'usage' => array_intersect_key($response['usage'], array_flip([
        'input_tokens',
        'output_tokens',
      ])),
    ];
  }

  /**
   * Checks the model, the answered questions and the usage counters.
   */
  private static function assertEnvelope(array $response, $answers, array $questions): void {
    $model = $response['model'] ?? NULL;
    if (!is_string($model) || trim($model) === '' || strlen($model) > 255) {
      self::invalid();
    }
    if (!is_array($answers) || !self::sameKeys($questions, $answers)) {
      self::invalid();
    }
    foreach (['input_tokens', 'output_tokens'] as $field) {
      if (!is_int($response['usage'][$field] ?? NULL) || $response['usage'][$field] < 0) {
        self::invalid();
      }
    }
  }

  /**
   * Validates and normalizes the answer to one question.
   */
  private static function normalizeAnswer($answer, array $question): array {
    if (!is_array($answer) || ($answer['type'] ?? NULL) !== $question['type']) {
      self::invalid();
    }
    if ($question['type'] === 'noul') {
      self::probability($answer['noul'] ?? NULL);
      return ['type' => 'noul', 'noul' => $answer['noul']];
    }
    self::probability($answer['confidence'] ?? NULL);
    $criteria = $question['criteria'];
    $probabilities = self::probabilities($answer['probabilities'] ?? NULL, $criteria);
    $clean = [
      'type' => $question['type'],
      'confidence' => $answer['confidence'],
      'probabilities' => $probabilities,
    ];
    if ($question['type'] === 'choice') {
      $clean['choice'] = self::choice($answer['choice'] ?? NULL, $criteria, $probabilities);
    }
    else {
      $clean['score'] = self::score($answer['score'] ?? NULL, $criteria);
      $clean['legend'] = self::legend($answer['legend'] ?? NULL, $criteria);
    }
    return $clean;
  }

  /**
   * Validates the probabilities of the criteria.
   */
  private static function probabilities($probabilities, array $criteria): array {
    if (!is_array($probabilities) || !self::sameKeys($criteria, $probabilities)) {
      self::invalid();
    }
    foreach ($probabilities as $value) {
      self::probability($value);
    }
    // Accept rounding of each probability to two decimal places.
    if (abs(array_sum($probabilities) - 1) > 0.005 * count($probabilities) + 1.0E-9) {
      self::invalid();
    }
    return $probabilities;
  }

  /**
   * Validates the chosen category: a criterion with the highest probability.
   */
  private static function choice($choice, array $criteria, array $probabilities): string {
    if (!is_string($choice) || !array_key_exists($choice, $criteria) || $probabilities[$choice] < max($probabilities)) {
      self::invalid();
    }
    return $choice;
  }

  /**
   * Validates a score within the levels of the criteria.
   */
  private static function score($score, array $criteria): int|float {
    if ((!is_float($score) && !is_int($score)) || !is_finite((float) $score) || $score < 0 || $score > count($criteria) - 1) {
      self::invalid();
    }
    return $score;
  }

  /**
   * Validates the legend: one text per criterion.
   */
  private static function legend($legend, array $criteria): array {
    if (!is_array($legend) || !self::sameKeys($criteria, $legend)) {
      self::invalid();
    }
    foreach ($legend as $description) {
      if (!is_string($description)) {
        self::invalid();
      }
    }
    return $legend;
  }

  /**
   * Whether two arrays have exactly the same keys.
   */
  private static function sameKeys(array $expected, array $actual): bool {
    return !array_diff_key($expected, $actual) && !array_diff_key($actual, $expected);
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
