<?php

namespace Drupal\vactory_webform_typesafe\Service;

/**
 * Validates and translates administrator-defined classification questions. */
final class QuestionDefinition {

  /**
   * Returns editable starter questions for contact and support forms.
   */
  public static function defaults(): array {
    return [
      'intent' => [
        'label' => 'Objet principal de la demande',
        'type' => 'choice',
        'instructions' => 'Identifiez la demande principale. Choisissez la catégorie unclear si les informations sont insuffisantes.',
        'criteria' => [
          'inquiry' => 'Demande de renseignements',
          'complaint' => 'Réclamation',
          'quotation' => 'Demande de devis',
          'support' => 'Demande d’assistance',
          'refund' => 'Demande de remboursement',
          'other' => 'Autre demande',
          'unclear' => 'Informations insuffisantes',
        ],
        'confidence_threshold' => 0.75,
      ],
      'sentiment' => [
        'label' => 'Sentiment exprimé',
        'type' => 'choice',
        'instructions' => 'Identifiez le sentiment explicitement exprimé dans le message.',
        'criteria' => [
          'satisfied' => 'Satisfaction',
          'neutral' => 'Neutre',
          'frustrated' => 'Frustration',
          'angry' => 'Colère',
          'unclear' => 'Indéterminé',
        ],
        'confidence_threshold' => 0.75,
      ],
      'urgency' => [
        'label' => 'Degré d’urgence',
        'type' => 'score',
        'instructions' => 'Évaluez l’urgence à partir des délais et des conséquences explicitement mentionnés, sans vous baser uniquement sur la colère.',
        'criteria' => [
          'Faible : aucun délai ni impact immédiat',
          'Normal : demande courante',
          'Élevé : échéance proche explicitement mentionnée ou perturbation importante en cours',
        ],
        'confidence_threshold' => 0.75,
      ],
      'callback' => [
        'label' => 'Rappel téléphonique demandé',
        'type' => 'noul',
        'instructions' => 'La personne demande explicitement à être rappelée par téléphone.',
        'threshold' => 0.5,
        'review_min' => 0.35,
        'review_max' => 0.65,
      ],
    ];
  }

  /**
   * Validates stored questions before they can reach the provider.
   */
  public static function parse(string $json): array {
    $questions = json_decode($json, TRUE, 32, JSON_THROW_ON_ERROR);
    if (!is_array($questions) || !$questions || count($questions) > 20) {
      throw new \InvalidArgumentException('Configure between 1 and 20 questions.');
    }
    foreach ($questions as $id => $q) {
      if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', (string) $id) || !is_array($q) || empty($q['label']) || !is_string($q['label'])) {
        throw new \InvalidArgumentException('Questions need unique machine-name keys and labels.');
      }
      foreach ([
        'confidence_threshold',
        'threshold',
        'review_min',
        'review_max',
      ] as $key) {
        if (isset($q[$key]) && (!is_numeric($q[$key]) || $q[$key] < 0 || $q[$key] > 1)) {
          throw new \InvalidArgumentException('Thresholds must be between 0 and 1.');
        }
      }
      if (($q['type'] ?? '') === 'noul' && (($q['review_min'] ?? 0.35) > ($q['threshold'] ?? 0.5) || ($q['threshold'] ?? 0.5) > ($q['review_max'] ?? 0.65))) {
        throw new \InvalidArgumentException('The Noul threshold must lie within its review interval.');
      }
    }
    foreach ($questions as $question) {
      $type = $question['type'] ?? '';
      if (!in_array($type, ['choice', 'score', 'noul'], TRUE) || !is_string($question['instructions'] ?? NULL) || trim($question['instructions']) === '') {
        throw new \InvalidArgumentException('Each question needs a supported type and nonempty instructions.');
      }
      $criteria = $question['criteria'] ?? NULL;
      if ($type === 'choice') {
        if (!is_array($criteria) || count($criteria) < 2 || count($criteria) > 255) {
          throw new \InvalidArgumentException('Choice needs between 2 and 255 categories.');
        }
        foreach ($criteria as $label => $description) {
          if (trim((string) $label) === '' || strlen((string) $label) > 128 || ($description !== NULL && (!is_string($description) || trim($description) === ''))) {
            throw new \InvalidArgumentException('Choice categories need nonempty keys and text descriptions.');
          }
        }
      }
      elseif ($type === 'score') {
        if (!is_array($criteria) || !array_is_list($criteria) || count($criteria) < 2 || count($criteria) > 10) {
          throw new \InvalidArgumentException('Score needs 2 to 10 ordered levels.');
        }
        foreach ($criteria as $description) {
          if (!is_string($description) || trim($description) === '') {
            throw new \InvalidArgumentException('Each scoring level needs a description.');
          }
        }
      }
      elseif (isset($criteria)) {
        if (!is_array($criteria) || array_diff(array_keys($criteria), ['true', 'false'])) {
          throw new \InvalidArgumentException('Noul criteria can describe only true and false.');
        }
        foreach ($criteria as $description) {
          if (!is_string($description) || trim($description) === '') {
            throw new \InvalidArgumentException('Noul descriptions must be nonempty text.');
          }
        }
      }
    }
    return $questions;
  }

  /**
   * Removes local review settings from the provider payload.
   */
  public static function apiQuestions(array $questions): array {
    return array_map(static fn(array $q) => array_intersect_key($q, array_flip([
      'type',
      'instructions',
      'criteria',
    ])), $questions);
  }

  /**
   * Evaluates confidence and Noul uncertainty using separate rules.
   */
  public static function needsReview(array $questions, array $answers): bool {
    foreach ($questions as $id => $q) {
      $answer = $answers[$id];
      if ($q['type'] === 'noul') {
        if ($answer['noul'] >= ($q['review_min'] ?? 0.35) && $answer['noul'] <= ($q['review_max'] ?? 0.65)) {
          return TRUE;
        }
      }
      elseif ($answer['confidence'] < ($q['confidence_threshold'] ?? 0.75)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns the effective scalar representation of a model answer.
   */
  public static function displayValue(array $question, string $value): string {
    return match ($question['type']) {
      'choice' => $question['criteria'][$value] ?? $value,
      'noul' => $value === 'yes' ? 'Oui' : 'Non',
      default => $value,
    };
  }

  /**
   * Returns the effective scalar representation of a model answer.
   */
  public static function value(array $question, array $answer): string {
    return match ($question['type']) {
      'choice' => $answer['choice'],
      'score' => (string) $answer['score'],
      'noul' => $answer['noul'] >= ($question['threshold'] ?? 0.5) ? 'yes' : 'no',
    };
  }

}
