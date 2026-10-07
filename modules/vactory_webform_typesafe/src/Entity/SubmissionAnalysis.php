<?php

namespace Drupal\vactory_webform_typesafe\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Stores the current classification, separate from submitted content.
 *
 * @ContentEntityType(
 *   id = "vactory_webform_analysis",
 *   label = @Translation("Webform classification"),
 *   base_table = "vactory_webform_analysis",
 *   handlers = {
 *     "access" = "Drupal\vactory_webform_typesafe\Access\SubmissionAnalysisAccessControlHandler"
 *   },
 *   entity_keys = {"id" = "id"}
 * )
 */
final class SubmissionAnalysis extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // The submission ID is also the primary key: at most one current analysis.
    $fields['id'] = BaseFieldDefinition::create('integer')->setLabel(t('Submission ID'))->setReadOnly(TRUE)->setRequired(TRUE);
    $fields['submission'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Submission'))->setSetting('target_type', 'webform_submission')->setRequired(TRUE);
    $fields['webform'] = BaseFieldDefinition::create('string')->setLabel(t('Webform'))->setRequired(TRUE);
    foreach ([
      'status',
      'review_status',
      'fingerprint',
      'generation',
      'model',
    ] as $name) {
      $fields[$name] = BaseFieldDefinition::create('string')->setLabel($name);
    }
    foreach (['results', 'questions', 'overrides', 'error'] as $name) {
      $fields[$name] = BaseFieldDefinition::create('string_long')->setLabel($name);
    }
    foreach (['attempts', 'reviewer', 'reviewed', 'changed'] as $name) {
      $fields[$name] = BaseFieldDefinition::create('integer')->setLabel($name)->setDefaultValue(0);
    }
    $fields['labels'] = BaseFieldDefinition::create('string')->setLabel(t('Effective classifications'))->setCardinality(-1);
    return $fields;
  }

  /**
   * Decodes a JSON field without exposing it through entity routes.
   */
  public function decoded(string $field): array {
    return json_decode($this->get($field)->value ?? '{}', TRUE) ?: [];
  }

}
