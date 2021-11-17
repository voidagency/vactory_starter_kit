<?php

namespace Drupal\vactory_quiz\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Quiz Question field type definition.
 *
 * @FieldType(
 *   id="vactory_quiz_question",
 *   label=@Translation("Quiz Question"),
 *   default_formatter="vactory_quiz_question_formatter",
 *   default_widget="vactory_quiz_question_widget"
 * )
 */
class QuizQuestionItem extends FieldItemBase {

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['question_number'] = DataDefinition::create('string');
    $properties['question_text_value'] = DataDefinition::create('string');
    $properties['question_text_format'] = DataDefinition::create('string');
    $properties['question_type'] = DataDefinition::create('string');
    $properties['question_answers'] = DataDefinition::create('string');
    $properties['question_reward'] = DataDefinition::create('integer');
    $properties['question_penalty'] = DataDefinition::create('integer');
    return $properties;
  }

  /**
   * {@inheritDoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'question_number' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ],
        'question_text_value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'question_text_format' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'question_type' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ],
        'question_answers' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'question_reward' => [
          'type' => 'int',
          'size' => 'medium',
          'default' => 1,
          'not null' => TRUE,
        ],
        'question_penalty' => [
          'type' => 'int',
          'size' => 'medium',
          'default' => 0,
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $question_value = $this->get('question_text_value')->getValue();
    $question_answers = $this->get('question_answers')->getValue();
    return empty($question_value) || empty($question_answers);
  }

}
