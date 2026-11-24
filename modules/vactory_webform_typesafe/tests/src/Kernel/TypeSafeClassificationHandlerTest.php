<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Core\Form\FormState;

require_once __DIR__ . '/TypeSafeKernelTestBase.php';

/**
 * Characterizes TypeSafeClassificationHandler::validateConfigurationForm().
 *
 * @group vactory_webform_typesafe
 */
class TypeSafeClassificationHandlerTest extends TypeSafeKernelTestBase {

  /**
   * The generic question-row error message.
   */
  protected const ROWS_ERROR = 'Invalid question definitions. Check unique machine names, labels, instructions, criteria and thresholds.';

  /**
   * The handler under test.
   *
   * @var \Drupal\vactory_webform_typesafe\Plugin\WebformHandler\TypeSafeClassificationHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $webform = $this->createTypeSafeWebform();
    $this->handler = $webform->getHandler('classification');
    $this->handler->setWebform($webform);
  }

  /**
   * Builds a full question row with defaults for omitted keys.
   */
  protected function row(array $values): array {
    return $values + [
      'id' => '',
      'label' => '',
      'type' => 'choice',
      'instructions' => '',
      'criteria' => '',
      'confidence_threshold' => '0.75',
      'threshold' => '0.5',
      'review_min' => '0.35',
      'review_max' => '0.65',
      'remove' => 0,
    ];
  }

  /**
   * Runs validation for the given rows and returns the form state.
   */
  protected function validate(array $rows, ?array $fields = NULL): FormState {
    $fields ??= ['message' => 'message', 'subject' => 0];
    $form = [];
    $state = new FormState();
    $state->setValues([
      'question_rows' => $rows,
      'fields' => $fields,
      'automatic' => 0,
      'on_update' => 0,
      'model' => '',
    ]);
    $this->handler->validateConfigurationForm($form, $state);
    return $state;
  }

  /**
   * Asserts the state has exactly the given errors, as strings.
   */
  protected function assertErrors(array $expected, FormState $state): void {
    $this->assertSame($expected, array_map('strval', $state->getErrors()));
  }

  /**
   * Rows become trimmed definitions; removed and blank rows are skipped.
   */
  public function testValidRowsAreSerialized(): void {
    $state = $this->validate([
      $this->row([
        'id' => '  intent ',
        'label' => ' Intent ',
        'type' => 'choice',
        'instructions' => "  Pick one.\n ",
        'criteria' => "  refund |  Wants money back \r\n\n other|Other | with pipe\n   \n",
        'confidence_threshold' => '0.8',
      ]),
      // Removed rows are skipped even when invalid.
      $this->row([
        'id' => 'Bad ID',
        'label' => '',
        'type' => 'score',
        'remove' => 1,
      ]),
      // Rows with blank id, label and instructions are skipped, whatever
      // their criteria or type.
      $this->row([
        'id' => '  ',
        'label' => ' ',
        'instructions' => '',
        'criteria' => 'garbage',
      ]),
      $this->row([
        'id' => 'urgency',
        'label' => 'Urgency',
        'type' => 'score',
        'instructions' => 'Rate it.',
        'criteria' => " Low \nHigh  ",
        'confidence_threshold' => '',
      ]),
      $this->row([
        'id' => 'callback',
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Wants a call.',
        'criteria' => 'ignored | for noul',
        'confidence_threshold' => '0.9',
        'threshold' => '0.4',
        'review_min' => '0.2',
        'review_max' => '0.6',
      ]),
    ]);
    $this->assertErrors([], $state);
    $expected = [
      'intent' => [
        'label' => 'Intent',
        'type' => 'choice',
        'instructions' => 'Pick one.',
        'criteria' => [
          'refund' => 'Wants money back',
          'other' => 'Other | with pipe',
        ],
        'confidence_threshold' => 0.8,
      ],
      'urgency' => [
        'label' => 'Urgency',
        'type' => 'score',
        'instructions' => 'Rate it.',
        'criteria' => ['Low', 'High'],
        'confidence_threshold' => 0.0,
      ],
      'callback' => [
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Wants a call.',
        'threshold' => 0.4,
        'review_min' => 0.2,
        'review_max' => 0.6,
      ],
    ];
    $json = $state->getValue('questions');
    $this->assertSame(json_encode($expected, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $json);
    // JSON drops the zero fraction: an empty threshold is stored as 0.
    $this->assertSame(0, json_decode($json, TRUE)['urgency']['confidence_threshold']);
  }

  /**
   * Non-ASCII text is stored unescaped.
   */
  public function testUnicodeIsUnescaped(): void {
    $state = $this->validate([
      $this->row([
        'id' => 'sentiment',
        'label' => 'Sentiment exprimé',
        'instructions' => 'Identifiez l’émotion.',
        'criteria' => "colere | Colère\nneutre | Neutre",
      ]),
    ]);
    $this->assertErrors([], $state);
    $this->assertStringContainsString('Sentiment exprimé', $state->getValue('questions'));
    $this->assertStringContainsString('Colère', $state->getValue('questions'));
  }

  /**
   * Every invalid definition produces the same question_rows error.
   *
   * @dataProvider providerInvalidRows
   */
  public function testInvalidRows(array $rows): void {
    $rows = array_map([$this, 'row'], $rows);
    $state = $this->validate($rows);
    $this->assertErrors(['question_rows' => self::ROWS_ERROR], $state);
    $this->assertNull($state->getValue('questions'));
  }

  /**
   * Provides invalid question rows.
   */
  public static function providerInvalidRows(): array {
    $choice = [
      'id' => 'intent',
      'label' => 'Intent',
      'type' => 'choice',
      'instructions' => 'Pick one.',
      'criteria' => "a | A\nb | B",
    ];
    $noul = [
      'id' => 'callback',
      'label' => 'Callback',
      'type' => 'noul',
      'instructions' => 'Wants a call.',
    ];
    $score = ['type' => 'score', 'criteria' => 'Only'];
    $eleven = [
      'type' => 'score',
      'criteria' => implode("\n", range(1, 11)),
    ];
    $above_one = ['review_max' => '1.2', 'threshold' => '0.9'];
    return [
      'no rows' => [[]],
      'only removed rows' => [[['remove' => 1] + $choice]],
      'only blank rows' => [[['criteria' => 'x | y']]],
      'duplicate id after trim' => [[$choice, ['id' => ' intent '] + $choice]],
      'choice line without pipe' => [[['criteria' => "a | A\nb"] + $choice]],
      'duplicate category' => [[['criteria' => "a | A\n a |B"] + $choice]],
      'single category' => [[['criteria' => 'a | A'] + $choice]],
      'empty category description' => [[['criteria' => "a | A\nb |  "] + $choice]],
      'empty category key' => [[['criteria' => "a | A\n | B"] + $choice]],
      'invalid machine name' => [[['id' => 'Intent-1'] + $choice]],
      'id starting with digit' => [[['id' => '1intent'] + $choice]],
      'empty id with label' => [[['id' => ' '] + $choice]],
      'empty label' => [[['label' => '  '] + $choice]],
      'empty instructions' => [[['instructions' => ' '] + $choice]],
      'unsupported type' => [[['type' => 'other'] + $choice]],
      'score single level' => [[$score + $choice]],
      'score eleven levels' => [[$eleven + $choice]],
      'confidence above one' => [[['confidence_threshold' => '1.5'] + $choice]],
      'negative confidence' => [[['confidence_threshold' => '-0.1'] + $choice]],
      'noul threshold below review_min' => [[['threshold' => '0.3'] + $noul]],
      'noul threshold above review_max' => [[['threshold' => '0.7'] + $noul]],
      'noul review_max above one' => [[$above_one + $noul]],
      'twenty one questions' => [array_map(static fn($i) => ['id' => 'q' . $i] + $choice, range(1, 21))],
    ];
  }

  /**
   * Twenty questions is the accepted maximum.
   */
  public function testTwentyQuestionsAccepted(): void {
    $rows = array_map(fn($i) => $this->row([
      'id' => 'q' . $i,
      'label' => 'Q' . $i,
      'instructions' => 'Pick.',
      'criteria' => "a | A\nb | B",
    ]), range(1, 20));
    $state = $this->validate($rows);
    $this->assertErrors([], $state);
    $this->assertCount(20, json_decode($state->getValue('questions'), TRUE));
  }

  /**
   * A field selection without any checked value is rejected.
   */
  public function testNoFieldSelected(): void {
    $rows = [
      $this->row([
        'id' => 'callback',
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Wants a call.',
      ]),
    ];
    $state = $this->validate($rows, ['message' => 0, 'subject' => 0]);
    $this->assertErrors(['fields' => 'Select at least one eligible text field.'], $state);
    // Questions are still serialized when only the fields are invalid.
    $this->assertNotNull($state->getValue('questions'));

    $state = $this->validate([], []);
    $this->assertErrors([
      'question_rows' => self::ROWS_ERROR,
      'fields' => 'Select at least one eligible text field.',
    ], $state);
  }

  /**
   * Missing question_rows and fields values are treated as empty.
   */
  public function testMissingValues(): void {
    $form = [];
    $state = new FormState();
    $this->handler->validateConfigurationForm($form, $state);
    $this->assertErrors([
      'question_rows' => self::ROWS_ERROR,
      'fields' => 'Select at least one eligible text field.',
    ], $state);
  }

}
