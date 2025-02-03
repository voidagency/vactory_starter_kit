<?php

namespace Drupal\vactory_dynamic_import\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Transliteration\PhpTransliteration;

/**
 * Service for term normalization and validation.
 *
 * This service handles the validation and normalization of taxonomy terms
 * in CSV imports, ensuring consistency in term naming across the import.
 */
class TermNormalizationService {
  use StringTranslationTrait;
  protected PhpTransliteration $transliteration;

  /**
   * __construct.
   *
   * @param \Drupal\Core\Transliteration\PhpTransliteration $transliteration
   *
   * @return void
   */
  public function __construct(PhpTransliteration $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * Validates terms in CSV data for consistency.
   *
   * @param string $file_path
   *   Path to the CSV file.
   * @param array $header
   *   CSV header array.
   * @param string $delimiter
   *   CSV delimiter.
   *
   * @return array
   *   Validation results containing:
   *   - status: Boolean indicating if validation passed
   *   - errors: Array of error messages if any
   *   - term_fields: Boolean indicating if term fields were found
   */
  public function validateTerms($file_path, array $header, $delimiter) {
    $term_columns = $this->identifyTermColumns($header);

    if (empty($term_columns)) {
      return [
        'status' => TRUE,
        'term_fields' => FALSE,
      ];
    }

    $handle = $this->openCsvFile($file_path);
    if (!$handle) {
      return [
        'status' => FALSE,
        'message' => $this->t('Could not open CSV file'),
      ];
    }

    try {
      $validation_results = $this->processTerms($handle, $term_columns, $delimiter);
    }
    finally {
      fclose($handle);
    }

    return $validation_results;
  }

  /**
   * Identifies term columns from CSV header.
   *
   * @param array $header
   *   CSV header array.
   *
   * @return array
   *   Array of term column indices and their configurations.
   */
  protected function identifyTermColumns(array $header) {
    $term_columns = [];
    foreach ($header as $index => $field) {
      if (strpos($field, 'term|') === 0) {
        $term_columns[$index] = $field;
      }
    }
    return $term_columns;
  }

  /**
   * Opens and validates CSV file.
   *
   * @param string $file_path
   *   Path to the CSV file.
   *
   * @return resource|false
   *   File handle if successful, false otherwise.
   */
  protected function openCsvFile($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
      return FALSE;
    }
    return fopen($file_path, 'r');
  }

  /**
   * Processes terms from CSV file and validates them.
   *
   * @param resource $handle
   *   File handle for CSV.
   * @param array $term_columns
   *   Array of term column indices.
   * @param string $delimiter
   *   CSV delimiter.
   *
   * @return array
   *   Validation results.
   */
  protected function processTerms($handle, array $term_columns, $delimiter) {
    // Skip header row.
    fgetcsv($handle, 0, $delimiter);

    $term_values = [];
    $errors = [];
    $line_number = 2;

    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      $this->processRow($row, $term_columns, $line_number, $term_values, $errors);
      $line_number++;
    }

    return [
      'status' => empty($errors),
      'errors' => $this->formatErrorMessages($errors),
      'term_fields' => TRUE,
    ];
  }

  /**
   * Processes a single row of CSV data.
   */
  protected function processRow(array $row, array $term_columns, $line_number, array &$term_values, array &$errors) {
    foreach ($term_columns as $index => $field_config) {
      if (!isset($row[$index]) || empty($row[$index])) {
        continue;
      }

      $values = array_filter(
        explode('|', $row[$index]),
        function ($value) {
          return trim($value) !== '';
        }
      );

      foreach ($values as $value) {
        $this->validateTerm(
          trim($value),
          $field_config,
          $line_number,
          $term_values,
          $errors
        );
      }
    }
  }

  /**
   * Validates a single term value.
   */
  protected function validateTerm($value, $field_config, $line_number, array &$term_values, array &$errors) {
    // Normalize the value for comparison.
    $normalized_value = $this->normalizeForComparison($value);

    // Also create a stripped version (no spaces).
    $stripped_value = str_replace(' ', '', $normalized_value);

    // If no entries exist for this field config yet.
    if (!isset($term_values[$field_config])) {
      $term_values[$field_config] = [
        'originals' => [$value],
        'normalized_originals' => [$normalized_value],
        'stripped_originals' => [$stripped_value],
        'variations' => [],
        'duplicates' => [$line_number],
        'lines' => [$line_number],
      ];
      return;
    }

    // Check if the normalized value already exists.
    $normalized_index = array_search($normalized_value, $term_values[$field_config]['normalized_originals']);
    $stripped_index = array_search($stripped_value, $term_values[$field_config]['stripped_originals']);

    if ($normalized_index !== FALSE) {
      // If the normalized value exists, check for exact match.
      if ($term_values[$field_config]['originals'][$normalized_index] === $value) {
        // Exact duplicate.
        $term_values[$field_config]['duplicates'][] = $line_number;
        return;
      }
    }
    // Identify differences if not an exact match.
    $differences = [];
    if ($normalized_index !== FALSE) {
      $original = $term_values[$field_config]['originals'][$normalized_index];
      $differences = $this->identifyDifferences($value, $original);
    }
    // Check for missing characters before checking stripped version.
    elseif ($this->hasMissingCharacterDifference($value, $term_values[$field_config]['originals'][0])) {
      $differences = ['missing_characters'];
    }
    elseif ($stripped_index !== FALSE) {
      // If stripped versions match but normalized don't.
      $differences = ['word_boundaries'];
    }

    // Add variation or new original.
    if (!empty($differences)) {
      $this->addVariation($value, $field_config, $line_number, $term_values, $errors, $differences);
    }
    else {
      // Add as a new original.
      $term_values[$field_config]['originals'][] = $value;
      $term_values[$field_config]['normalized_originals'][] = $normalized_value;
      $term_values[$field_config]['stripped_originals'][] = $stripped_value;
      $term_values[$field_config]['lines'][] = $line_number;
    }
  }

  /**
   * Identifies differences between two terms.
   */
  protected function identifyDifferences($term1, $term2): array {
    $differences = [];

    // Check for case differences.
    if (strtolower($term1) === strtolower($term2) && $term1 !== $term2) {
      $differences[] = 'case';
    }

    // Check for accent differences.
    $normalized1 = $this->transliteration->transliterate($term1);
    $normalized2 = $this->transliteration->transliterate($term2);
    if ($normalized1 === $normalized2 && $term1 !== $term2) {
      $differences[] = 'accents';
    }

    // Enhanced word boundary check.
    if ($this->hasWordBoundaryDifference($term1, $term2)) {
      $differences[] = 'word_boundaries';
    }

    if ($this->hasMissingCharacterDifference($term1, $term2)) {
      $differences[] = 'missing_characters';
    }

    // Add similarity percentage check.
    $similarity_percent = 0;
    similar_text($term1, $term2, $similarity_percent);

    // If similarity is high but not 100%, add a variation flag.
    if ($similarity_percent > 90 && $similarity_percent < 100) {
      $differences[] = sprintf('similarity_%d', round($similarity_percent));
    }

    return $differences;
  }

  /**
   * Enhanced check for word boundary differences.
   */
  protected function hasWordBoundaryDifference($term1, $term2): bool {
    // First normalize both terms.
    $norm1 = $this->normalizeForComparison($term1);
    $norm2 = $this->normalizeForComparison($term2);

    // If they're exactly the same after normalization but different originally.
    if ($norm1 === $norm2 && $term1 !== $term2) {
      return TRUE;
    }

    // Check for joined words vs separated words.
    $words1 = $this->splitIntoWords($term1);
    $words2 = $this->splitIntoWords($term2);

    // If one has more words than the other.
    if (count($words1) !== count($words2)) {
      $joined1 = strtolower(implode('', $words1));
      $joined2 = strtolower(implode('', $words2));

      if ($joined1 === $joined2) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if two terms differ by a missing character.
   */
  protected function hasMissingCharacterDifference($term1, $term2): bool {
    // Normalize both terms for comparison.
    $norm1 = $this->normalizeForComparison($term1);
    $norm2 = $this->normalizeForComparison($term2);

    // If the terms are already identical after normalization, no missing char.
    if ($norm1 === $norm2) {
      return FALSE;
    }

    // Split terms into words.
    $words1 = $this->splitIntoWords($norm1);
    $words2 = $this->splitIntoWords($norm2);

    // Single word case.
    if (count($words1) === 1 && count($words2) === 1) {
      return $this->isMissingCharacterInWord($words1[0], $words2[0]);
    }

    // If the number of words is diff, it's not a missing char diff.
    if (count($words1) !== count($words2)) {
      return FALSE;
    }

    // Compare each word pair.
    $foundMissingChar = FALSE;
    $differences = 0;
    for ($i = 0; $i < count($words1); $i++) {
      if ($words1[$i] !== $words2[$i]) {
        if ($this->isMissingCharacterInWord($words1[$i], $words2[$i])) {
          $foundMissingChar = TRUE;
        }
        $differences++;
      }
    }
    // Only return true if exactly one word has a missing character.
    // and no other differences exist.
    return $foundMissingChar && $differences === 1;
  }

  /**
   * Checks if two words differ by a missing character.
   */
  protected function isMissingCharacterInWord($word1, $word2): bool {
    // Normalize the words first.
    $word1 = mb_strtolower($word1);
    $word2 = mb_strtolower($word2);

    $len1 = mb_strlen($word1);
    $len2 = mb_strlen($word2);

    // If the lengths diff by more than 1, it's not a missing character diff.
    if (abs($len1 - $len2) !== 1) {
      return FALSE;
    }

    // Determine which word is longer.
    $longer = $len1 > $len2 ? $word1 : $word2;
    $shorter = $len1 > $len2 ? $word2 : $word1;

    // Check if shorter word can be formed by removing one char from longer.
    for ($i = 0; $i < mb_strlen($longer); $i++) {
      $temp = mb_substr($longer, 0, $i) . mb_substr($longer, $i + 1);
      if ($temp === $shorter) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Splits a term into words considering various separators.
   */
  protected function splitIntoWords($term): array {
    // First, handle CamelCase.
    $term = preg_replace('/(?<!^)(?=[A-Z])/', ' $0', $term);

    // Handle numbers as word boundaries.
    $term = preg_replace('/(?<=\d)(?=\D)|(?<=\D)(?=\d)/', ' ', $term);

    // Replace common separators with spaces.
    $term = str_replace(['-', '_', '.', '/', '#'], ' ', $term);

    // Split on spaces and filter empty values.
    return array_values(array_filter(
      explode(' ', $term),
      function ($word) {
        return trim($word) !== '';
      }
    ));
  }

  /**
   * Normalizes a term for comparison.
   */
  protected function normalizeForComparison($term): string {
    // Convert to lowercase.
    $term = mb_strtolower($term);

    // Remove accents.
    $term = $this->transliteration->transliterate($term);

    // Handle CamelCase.
    $term = preg_replace('/(?<!^)(?=[A-Z])/', ' $0', $term);

    // Replace all word boundaries with a single space.
    $term = preg_replace('/[-_.\/# ]+/', ' ', $term);

    // Clean up and return.
    return trim($term);
  }

  /**
   * Adds a variation to the term values and errors.
   */
  protected function addVariation($value, $field_config, $line_number, array &$term_values, array &$errors, array $differences = []) {
    $term_values[$field_config]['variations'][] = [
      'value' => $value,
      'differences' => $differences,
    ];
    $term_values[$field_config]['lines'][] = $line_number;

    if (!isset($errors[$field_config])) {
      $errors[$field_config] = [
        'field' => $field_config,
        'originals' => $term_values[$field_config]['originals'],
        'variations' => [
        [
          'value' => $value,
          'differences' => $differences,
          'lines' => [$line_number],
        ],
        ],
        'duplicates' => $term_values[$field_config]['duplicates'],
        'lines' => $term_values[$field_config]['lines'],
      ];
    }
    else {
      $errors[$field_config]['variations'][] = [
        'value' => $value,
        'differences' => $differences,
        'lines' => [$line_number],
      ];
      $errors[$field_config]['lines'][] = $line_number;
    }
  }

  /**
   * Formats error messages for term validation issues.
   */
  protected function formatErrorMessages(array $errors) {
    $error_messages = [];
    foreach ($errors as $field => $data) {
      $message = $this->t('Different variations found for field "@field":', [
        '@field' => $field,
      ]) . '<br/>';

      $message .= $this->t('Terms must be exactly identical. The following variations were found:') . '<br/>';

      // Show all originals.
      $message .= $this->t('- Valid originals:') . '<br/>';
      foreach ($data['originals'] as $original) {
        $message .= '  • "' . $original . '"<br/>';
      }

      // Show variations with their differences.
      $message .= $this->t('- Variations found:') . '<br/>';
      foreach ($data['variations'] as $variation) {
        $message .= '  • "' . $variation['value'] . '"';
        if (!empty($variation['differences'])) {
          $diff_display = array_map(function ($diff) {
            // Special handling for similarity differences.
            if (strpos($diff, 'similarity_') === 0) {
              return 'similar (' . str_replace('similarity_', '', $diff) . '%)';
            }
            return $diff;
          }, $variation['differences']);
          $message .= ' (' . implode(', ', $diff_display) . ')';
        }
        if (!empty($variation['lines'])) {
          $message .= ' on line(s): ' . implode(', ', $variation['lines']);
        }
        $message .= '<br/>';
      }

      $error_messages[] = $message;
    }
    return $error_messages;
  }

}
