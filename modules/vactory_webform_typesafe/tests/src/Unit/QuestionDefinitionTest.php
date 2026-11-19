<?php

namespace Drupal\Tests\vactory_webform_typesafe\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;

/**
 * Characterizes QuestionDefinition::parse() before refactoring.
 *
 * @coversDefaultClass \Drupal\vactory_webform_typesafe\Service\QuestionDefinition
 * @group vactory_webform_typesafe
 */
class QuestionDefinitionTest extends UnitTestCase {

  private const COUNT = 'Configure between 1 and 20 questions.';
  private const IDENTITY = 'Questions need unique machine-name keys and labels.';
  private const THRESHOLD = 'Thresholds must be between 0 and 1.';
  private const NOUL_INTERVAL = 'The Noul threshold must lie within its review interval.';
  private const TYPE = 'Each question needs a supported type and nonempty instructions.';
  private const CHOICE_COUNT = 'Choice needs between 2 and 255 categories.';
  private const CHOICE_ITEM = 'Choice categories need nonempty keys and text descriptions.';
  private const SCORE_COUNT = 'Score needs 2 to 10 ordered levels.';
  private const SCORE_ITEM = 'Each scoring level needs a description.';
  private const NOUL_KEYS = 'Noul criteria can describe only true and false.';
  private const NOUL_ITEM = 'Noul descriptions must be nonempty text.';

  /**
   * A minimal valid question of each type.
   */
  private static function valid(): array {
    return [
      'intent' => [
        'label' => 'Intent',
        'type' => 'choice',
        'instructions' => 'Pick one.',
        'criteria' => ['a' => 'A', 'b' => 'B'],
        'confidence_threshold' => 0.75,
      ],
      'urgency' => [
        'label' => 'Urgency',
        'type' => 'score',
        'instructions' => 'Rate it.',
        'criteria' => ['Low', 'High'],
      ],
      'callback' => [
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Asks for a call.',
      ],
    ];
  }

  /**
   * Parses a PHP structure encoded as JSON.
   */
  private static function parseArray(array $questions): array {
    return QuestionDefinition::parse(json_encode($questions, JSON_THROW_ON_ERROR));
  }

  /**
   * Asserts parse() fails with the exact class and message.
   */
  private function assertRejected(string $json, string $message): void {
    try {
      QuestionDefinition::parse($json);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertSame(\InvalidArgumentException::class, get_class($e));
      $this->assertSame($message, $e->getMessage());
      return;
    }
    $this->fail('Expected rejection: ' . $message);
  }

  /**
   * Defaults validate and round-trip unchanged, including local settings.
   *
   * @covers ::parse
   * @covers ::defaults
   */
  public function testDefaultsRoundTrip(): void {
    $defaults = QuestionDefinition::defaults();
    $this->assertSame(['intent', 'sentiment', 'urgency', 'callback'], array_keys($defaults));
    $this->assertSame($defaults, self::parseArray($defaults));
    $pretty = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $this->assertSame($defaults, QuestionDefinition::parse($pretty));
  }

  /**
   * A minimal valid definition is returned exactly as decoded.
   *
   * @covers ::parse
   */
  public function testValidMinimalRoundTrip(): void {
    $valid = self::valid();
    $this->assertSame($valid, self::parseArray($valid));
  }

  /**
   * Invalid JSON escapes as JsonException.
   *
   * @covers ::parse
   */
  public function testInvalidJsonThrowsJsonException(): void {
    $this->expectException(\JsonException::class);
    QuestionDefinition::parse('{not json');
  }

  /**
   * Nesting deeper than 32 escapes as JsonException.
   *
   * @covers ::parse
   */
  public function testDepthLimitThrowsJsonException(): void {
    $this->expectException(\JsonException::class);
    QuestionDefinition::parse(str_repeat('[', 33) . str_repeat(']', 33));
  }

  /**
   * Count and top-level shape failures.
   *
   * @covers ::parse
   * @dataProvider providerCount
   */
  public function testCount(string $json): void {
    $this->assertRejected($json, self::COUNT);
  }

  /**
   * Data provider for testCount().
   */
  public static function providerCount(): array {
    $one = self::valid()['callback'];
    $many = [];
    for ($i = 0; $i < 21; $i++) {
      $many['q' . $i] = $one;
    }
    return [
      'empty object' => ['{}'],
      'empty list' => ['[]'],
      'null' => ['null'],
      'string' => ['"x"'],
      'number' => ['1'],
      'false' => ['false'],
      '21 questions' => [json_encode($many)],
    ];
  }

  /**
   * One and twenty questions are both accepted.
   *
   * @covers ::parse
   */
  public function testCountBoundaries(): void {
    $one = self::valid()['callback'];
    $this->assertCount(1, self::parseArray(['q' => $one]));
    $many = [];
    for ($i = 0; $i < 20; $i++) {
      $many['q' . $i] = $one;
    }
    $this->assertCount(20, self::parseArray($many));
  }

  /**
   * Identity failures: machine-name key, array shape, label.
   *
   * @covers ::parse
   * @dataProvider providerIdentity
   */
  public function testIdentity(string $json): void {
    $this->assertRejected($json, self::IDENTITY);
  }

  /**
   * Data provider for testIdentity().
   */
  public static function providerIdentity(): array {
    $q = self::valid()['callback'];
    $cases = [
      'list keys' => [json_encode([$q])],
      'uppercase start' => [json_encode(['Intent' => $q])],
      'digit start' => [json_encode(['1a' => $q])],
      'underscore start' => [json_encode(['_a' => $q])],
      'hyphen' => [json_encode(['a-b' => $q])],
      'space' => [json_encode(['a b' => $q])],
      'uppercase inside' => [json_encode(['aB' => $q])],
      '65 chars' => [json_encode([str_repeat('a', 65) => $q])],
      'empty key' => [json_encode(['' => $q])],
      'question is string' => [json_encode(['a' => 'x'])],
      'question is null' => [json_encode(['a' => NULL])],
      'missing label' => [json_encode(['a' => array_diff_key($q, ['label' => 1])])],
      'empty label' => [json_encode(['a' => ['label' => ''] + $q])],
      'zero string label' => [json_encode(['a' => ['label' => '0'] + $q])],
      'numeric label' => [json_encode(['a' => ['label' => 5] + $q])],
      'array label' => [json_encode(['a' => ['label' => ['x']] + $q])],
      'second question bad' => [json_encode(['a' => $q, 'B' => $q])],
    ];
    return $cases;
  }

  /**
   * Accepted identity edge cases.
   *
   * @covers ::parse
   */
  public function testIdentityAccepted(): void {
    $q = self::valid()['callback'];
    $ids = ['a', 'a_1', 'z9', str_repeat('a', 64)];
    $questions = array_fill_keys($ids, $q);
    $this->assertSame($ids, array_keys(self::parseArray($questions)));
    // A whitespace-only label is not empty().
    $this->assertSame(' ', self::parseArray(['a' => ['label' => ' '] + $q])['a']['label']);
  }

  /**
   * Threshold failures for each threshold key.
   *
   * @covers ::parse
   * @dataProvider providerThresholds
   */
  public function testThresholds(string $key, $value): void {
    $q = self::valid()['intent'];
    $q[$key] = $value;
    $this->assertRejected(json_encode(['a' => $q]), self::THRESHOLD);
  }

  /**
   * Data provider for testThresholds().
   */
  public static function providerThresholds(): array {
    $cases = [];
    foreach (['confidence_threshold', 'threshold', 'review_min', 'review_max'] as $key) {
      $cases["$key negative"] = [$key, -0.01];
      $cases["$key above one"] = [$key, 1.01];
      $cases["$key non numeric"] = [$key, 'abc'];
      $cases["$key bool"] = [$key, TRUE];
      $cases["$key array"] = [$key, [0.5]];
      $cases["$key numeric string too big"] = [$key, '2'];
    }
    return $cases;
  }

  /**
   * Accepted threshold values: bounds, numeric strings, null (unset).
   *
   * @covers ::parse
   */
  public function testThresholdsAccepted(): void {
    foreach ([0, 1, 0.25, '0.5', NULL] as $value) {
      $q = self::valid()['intent'];
      $q['confidence_threshold'] = $value;
      $q['threshold'] = $value;
      $parsed = self::parseArray(['a' => $q]);
      $this->assertSame($value, $parsed['a']['confidence_threshold']);
    }
  }

  /**
   * Noul interval: review_min <= threshold <= review_max with defaults.
   *
   * @covers ::parse
   * @dataProvider providerNoulInterval
   */
  public function testNoulInterval(array $overrides, bool $valid): void {
    $q = $overrides + self::valid()['callback'];
    $json = json_encode(['a' => $q]);
    if ($valid) {
      $this->assertSame(['a' => $q], QuestionDefinition::parse($json));
    }
    else {
      $this->assertRejected($json, self::NOUL_INTERVAL);
    }
  }

  /**
   * Data provider for testNoulInterval().
   */
  public static function providerNoulInterval(): array {
    return [
      'defaults' => [[], TRUE],
      'equal bounds' => [
        ['review_min' => 0.5, 'threshold' => 0.5, 'review_max' => 0.5],
        TRUE,
      ],
      'threshold at default min' => [['threshold' => 0.35], TRUE],
      'threshold at default max' => [['threshold' => 0.65], TRUE],
      'threshold below default min' => [['threshold' => 0.34], FALSE],
      'threshold above default max' => [['threshold' => 0.66], FALSE],
      'min above default threshold' => [['review_min' => 0.6], FALSE],
      'max below default threshold' => [['review_max' => 0.4], FALSE],
      'inverted interval' => [
        ['review_min' => 0.8, 'review_max' => 0.2, 'threshold' => 0.5],
        FALSE,
      ],
      'explicit wide interval' => [
        ['review_min' => 0, 'threshold' => 0.9, 'review_max' => 1],
        TRUE,
      ],
    ];
  }

  /**
   * The Noul interval is not checked for other types.
   *
   * @covers ::parse
   */
  public function testIntervalIgnoredForNonNoul(): void {
    foreach (['intent', 'urgency'] as $id) {
      $q = ['review_min' => 0.9, 'threshold' => 0.1, 'review_max' => 0.2] + self::valid()[$id];
      $this->assertSame(['a' => $q], self::parseArray(['a' => $q]));
    }
  }

  /**
   * Type and instructions failures.
   *
   * @covers ::parse
   * @dataProvider providerType
   */
  public function testType(array $overrides, array $unset = []): void {
    $q = array_diff_key($overrides + self::valid()['intent'], array_flip($unset));
    $this->assertRejected(json_encode(['a' => $q]), self::TYPE);
  }

  /**
   * Data provider for testType().
   */
  public static function providerType(): array {
    return [
      'missing type' => [[], ['type']],
      'unknown type' => [['type' => 'boolean']],
      'uppercase type' => [['type' => 'Choice']],
      'missing instructions' => [[], ['instructions']],
      'empty instructions' => [['instructions' => '']],
      'blank instructions' => [['instructions' => " \n\t"]],
      'numeric instructions' => [['instructions' => 5]],
      'array instructions' => [['instructions' => ['x']]],
    ];
  }

  /**
   * Choice criteria failures.
   *
   * @covers ::parse
   * @dataProvider providerChoice
   */
  public function testChoice(string $criteriaJson, string $message): void {
    $q = self::valid()['intent'];
    unset($q['criteria']);
    $json = substr(json_encode(['a' => $q]), 0, -2) . ',"criteria":' . $criteriaJson . '}}';
    $this->assertRejected($json, $message);
  }

  /**
   * Data provider for testChoice().
   */
  public static function providerChoice(): array {
    $many = [];
    for ($i = 0; $i < 256; $i++) {
      $many['k' . $i] = 'D';
    }
    return [
      'missing' => ['null', self::CHOICE_COUNT],
      'string' => ['"a"', self::CHOICE_COUNT],
      'one' => ['{"a":"A"}', self::CHOICE_COUNT],
      'empty' => ['{}', self::CHOICE_COUNT],
      '256' => [json_encode($many), self::CHOICE_COUNT],
      'empty key' => ['{"":"A","b":"B"}', self::CHOICE_ITEM],
      'blank key' => ['{" ":"A","b":"B"}', self::CHOICE_ITEM],
      '129 char key' => [
        '{"' . str_repeat('k', 129) . '":"A","b":"B"}',
        self::CHOICE_ITEM,
      ],
      'empty description' => ['{"a":"","b":"B"}', self::CHOICE_ITEM],
      'blank description' => ['{"a":"  ","b":"B"}', self::CHOICE_ITEM],
      'numeric description' => ['{"a":1,"b":"B"}', self::CHOICE_ITEM],
      'array description' => ['{"a":["x"],"b":"B"}', self::CHOICE_ITEM],
      'false description' => ['{"a":false,"b":"B"}', self::CHOICE_ITEM],
    ];
  }

  /**
   * Accepted choice edge cases.
   *
   * @covers ::parse
   */
  public function testChoiceAccepted(): void {
    $base = self::valid()['intent'];
    $many = [];
    for ($i = 0; $i < 255; $i++) {
      $many['k' . $i] = 'D';
    }
    $cases = [
      '255 categories' => $many,
      'null descriptions' => ['a' => NULL, 'b' => NULL],
      '128 char key' => [str_repeat('k', 128) => 'A', 'b' => 'B'],
      'numeric keys' => ['1' => 'One', '2' => 'Two'],
      // A JSON list is accepted: keys 0 and 1 are nonempty strings.
      'list' => ['A', 'B'],
    ];
    foreach ($cases as $label => $criteria) {
      $q = ['criteria' => $criteria] + $base;
      $this->assertSame(['a' => $q], self::parseArray(['a' => $q]), $label);
    }
  }

  /**
   * Score criteria failures.
   *
   * @covers ::parse
   * @dataProvider providerScore
   */
  public function testScore(string $criteriaJson, string $message): void {
    $q = self::valid()['urgency'];
    unset($q['criteria']);
    $json = substr(json_encode(['a' => $q]), 0, -2) . ',"criteria":' . $criteriaJson . '}}';
    $this->assertRejected($json, $message);
  }

  /**
   * Data provider for testScore().
   */
  public static function providerScore(): array {
    return [
      'missing' => ['null', self::SCORE_COUNT],
      'string' => ['"a"', self::SCORE_COUNT],
      'object keys' => ['{"a":"A","b":"B"}', self::SCORE_COUNT],
      'non sequential keys' => ['{"1":"A","2":"B"}', self::SCORE_COUNT],
      'one level' => ['["A"]', self::SCORE_COUNT],
      'eleven levels' => [
        json_encode(array_fill(0, 11, 'L')),
        self::SCORE_COUNT,
      ],
      'empty level' => ['["A",""]', self::SCORE_ITEM],
      'blank level' => ['["A"," "]', self::SCORE_ITEM],
      'null level' => ['["A",null]', self::SCORE_ITEM],
      'numeric level' => ['["A",2]', self::SCORE_ITEM],
    ];
  }

  /**
   * Accepted score edge cases (2 and 10 levels, sequential object keys).
   *
   * @covers ::parse
   */
  public function testScoreAccepted(): void {
    $base = self::valid()['urgency'];
    foreach ([2, 10] as $count) {
      $q = ['criteria' => array_fill(0, $count, 'L')] + $base;
      $this->assertSame(['a' => $q], self::parseArray(['a' => $q]));
    }
    $json = '{"a":{"label":"L","type":"score","instructions":"I","criteria":{"0":"A","1":"B"}}}';
    $this->assertSame(['A', 'B'], QuestionDefinition::parse($json)['a']['criteria']);
  }

  /**
   * Noul criteria failures.
   *
   * @covers ::parse
   * @dataProvider providerNoul
   */
  public function testNoul(string $criteriaJson, string $message): void {
    $q = self::valid()['callback'];
    $json = substr(json_encode(['a' => $q]), 0, -2) . ',"criteria":' . $criteriaJson . '}}';
    $this->assertRejected($json, $message);
  }

  /**
   * Data provider for testNoul().
   */
  public static function providerNoul(): array {
    return [
      'string' => ['"x"', self::NOUL_KEYS],
      'number' => ['1', self::NOUL_KEYS],
      'other key' => ['{"yes":"Y"}', self::NOUL_KEYS],
      'extra key' => ['{"true":"T","false":"F","maybe":"M"}', self::NOUL_KEYS],
      'list' => ['["T"]', self::NOUL_KEYS],
      'uppercase key' => ['{"TRUE":"T"}', self::NOUL_KEYS],
      'empty description' => ['{"true":""}', self::NOUL_ITEM],
      'blank description' => ['{"false":"  "}', self::NOUL_ITEM],
      'null description' => ['{"true":null}', self::NOUL_ITEM],
      'numeric description' => ['{"true":1}', self::NOUL_ITEM],
    ];
  }

  /**
   * Accepted Noul criteria: absent, null, empty, true and/or false.
   *
   * @covers ::parse
   */
  public function testNoulAccepted(): void {
    $base = self::valid()['callback'];
    $cases = [
      NULL,
      [],
      ['true' => 'T'],
      ['false' => 'F'],
      ['true' => 'T', 'false' => 'F'],
    ];
    foreach ($cases as $criteria) {
      $q = ['criteria' => $criteria] + $base;
      $this->assertSame(['a' => $q], self::parseArray(['a' => $q]));
    }
  }

  /**
   * Check order across and within questions.
   *
   * Identity and thresholds of all questions are checked before type and
   * criteria of any question; within one question identity, thresholds, then
   * the Noul interval; then type/instructions before criteria.
   *
   * @covers ::parse
   * @dataProvider providerOrder
   */
  public function testOrder(array $questions, string $message): void {
    $this->assertRejected(json_encode($questions), $message);
  }

  /**
   * Data provider for testOrder().
   */
  public static function providerOrder(): array {
    $v = self::valid();
    $badType = ['type' => 'nope'] + $v['intent'];
    $badCriteria = ['criteria' => ['a' => 'A']] + $v['intent'];
    $badLabel = ['label' => ''] + $v['intent'];
    $badThreshold = ['confidence_threshold' => 2] + $v['intent'];
    $badInterval = ['threshold' => 0.9] + $v['callback'];
    return [
      'type of first before label of second' => [
        ['a' => $badType, 'b' => $badLabel],
        self::IDENTITY,
      ],
      'criteria of first before threshold of second' => [
        ['a' => $badCriteria, 'b' => $badThreshold],
        self::THRESHOLD,
      ],
      'type of first before interval of second' => [
        ['a' => $badType, 'b' => $badInterval],
        self::NOUL_INTERVAL,
      ],
      'interval of first before label of second' => [
        ['a' => $badInterval, 'b' => $badLabel],
        self::NOUL_INTERVAL,
      ],
      'label before threshold in one question' => [
        ['a' => ['label' => '', 'confidence_threshold' => 2] + $v['intent']],
        self::IDENTITY,
      ],
      'threshold before interval in one question' => [
        ['a' => ['review_min' => 2, 'threshold' => 0.9] + $v['callback']],
        self::THRESHOLD,
      ],
      'instructions before criteria in one question' => [
        ['a' => ['instructions' => '', 'criteria' => []] + $v['intent']],
        self::TYPE,
      ],
      'criteria of first before type of second' => [
        ['a' => $badCriteria, 'b' => $badType],
        self::CHOICE_COUNT,
      ],
      'type of second only after criteria of first ok' => [
        ['a' => $v['intent'], 'b' => $badType],
        self::TYPE,
      ],
      'choice count before item check' => [
        ['a' => ['criteria' => ['' => 'A']] + $v['intent']],
        self::CHOICE_COUNT,
      ],
      'score list check before item check' => [
        ['a' => ['criteria' => ['x' => '', 'y' => '']] + $v['urgency']],
        self::SCORE_COUNT,
      ],
      'noul keys before descriptions' => [
        ['a' => ['criteria' => ['true' => '', 'maybe' => 'M']] + $v['callback']],
        self::NOUL_KEYS,
      ],
    ];
  }

}
