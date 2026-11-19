<?php

namespace Drupal\Tests\vactory_webform_typesafe\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Drupal\vactory_webform_typesafe\Service\JevResponseValidator;

/**
 * Characterizes JevResponseValidator::validate() before refactoring.
 *
 * @coversDefaultClass \Drupal\vactory_webform_typesafe\Service\JevResponseValidator
 * @group vactory_webform_typesafe
 */
class JevResponseValidatorTest extends UnitTestCase {

  private const MESSAGE = 'Jev returned an invalid classification response.';

  /**
   * One question of each type.
   */
  private static function questions(): array {
    return [
      'intent' => [
        'label' => 'Intent',
        'type' => 'choice',
        'instructions' => 'Pick one.',
        'criteria' => ['a' => 'A', 'b' => 'B', 'c' => NULL],
      ],
      'urgency' => [
        'label' => 'Urgency',
        'type' => 'score',
        'instructions' => 'Rate it.',
        'criteria' => ['Low', 'Normal', 'High'],
      ],
      'callback' => [
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Asks for a call.',
      ],
    ];
  }

  /**
   * A valid response for questions().
   */
  private static function response(): array {
    return [
      'model' => 'jev-test',
      'answers' => [
        'intent' => [
          'type' => 'choice',
          'choice' => 'a',
          'confidence' => 0.9,
          'probabilities' => ['a' => 0.7, 'b' => 0.2, 'c' => 0.1],
        ],
        'urgency' => [
          'type' => 'score',
          'score' => 1.2,
          'confidence' => 0.8,
          'probabilities' => [0.0, 0.8, 0.2],
          'legend' => ['Low', 'Normal', 'High'],
        ],
        'callback' => ['type' => 'noul', 'noul' => 0.8],
      ],
      'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
    ];
  }

  /**
   * Asserts validate() rejects with the exact exception.
   */
  private function assertInvalid(array $response, ?array $questions = NULL): void {
    try {
      JevResponseValidator::validate($response, $questions ?? self::questions());
    }
    catch (JevApiException $e) {
      $this->assertSame(self::MESSAGE, $e->getMessage());
      $this->assertTrue($e->retryable);
      $this->assertSame(0, $e->retryAfter);
      $this->assertNull($e->getPrevious());
      return;
    }
    $this->fail('Expected an invalid classification response.');
  }

  /**
   * Valid input is normalized: unknown fields are dropped.
   *
   * @covers ::validate
   */
  public function testNormalizedOutput(): void {
    $response = self::response();
    $response['extra'] = 'dropped';
    $response['usage']['total_tokens'] = 120;
    $response['answers']['intent']['reasoning'] = 'dropped';
    $response['answers']['urgency']['reasoning'] = 'dropped';
    $response['answers']['callback']['confidence'] = 0.1;
    // Response answer order differs from question order.
    $response['answers'] = array_reverse($response['answers'], TRUE);
    $result = JevResponseValidator::validate($response, self::questions());
    $this->assertSame([
      'answers' => [
        'intent' => [
          'type' => 'choice',
          'confidence' => 0.9,
          'probabilities' => ['a' => 0.7, 'b' => 0.2, 'c' => 0.1],
          'choice' => 'a',
        ],
        'urgency' => [
          'type' => 'score',
          'confidence' => 0.8,
          'probabilities' => [0.0, 0.8, 0.2],
          'score' => 1.2,
          'legend' => ['Low', 'Normal', 'High'],
        ],
        'callback' => ['type' => 'noul', 'noul' => 0.8],
      ],
      'model' => 'jev-test',
      'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
    ], $result);
  }

  /**
   * Usage keeps the response's own key order.
   *
   * @covers ::validate
   */
  public function testUsageKeyOrderPreserved(): void {
    $response = self::response();
    $response['usage'] = [
      'output_tokens' => 0,
      'cache' => 3,
      'input_tokens' => 0,
    ];
    $result = JevResponseValidator::validate($response, self::questions());
    $expected = ['output_tokens' => 0, 'input_tokens' => 0];
    $this->assertSame($expected, $result['usage']);
  }

  /**
   * Integer and boundary values are accepted and kept as given.
   *
   * @covers ::validate
   */
  public function testAcceptedEdgeValues(): void {
    $response = self::response();
    $response['model'] = str_repeat('m', 255);
    $response['answers']['intent'] = [
      'type' => 'choice',
      'choice' => 'b',
      'confidence' => 1,
      // Ties for the maximum are allowed.
      'probabilities' => ['a' => 0.5, 'b' => 0.5, 'c' => 0],
    ];
    $response['answers']['urgency']['score'] = 2;
    $response['answers']['urgency']['confidence'] = 0;
    $response['answers']['urgency']['probabilities'] = [0, 0, 1];
    $response['answers']['urgency']['legend'] = ['x', '', 'z'];
    $response['answers']['callback']['noul'] = 0;
    $result = JevResponseValidator::validate($response, self::questions());
    $this->assertSame(2, $result['answers']['urgency']['score']);
    $this->assertSame(['x', '', 'z'], $result['answers']['urgency']['legend']);
    $this->assertSame(1, $result['answers']['intent']['confidence']);
    $this->assertSame('b', $result['answers']['intent']['choice']);
    $this->assertSame(0, $result['answers']['callback']['noul']);
    $this->assertSame(str_repeat('m', 255), $result['model']);

    $response['answers']['urgency']['score'] = 0;
    $response['answers']['callback']['noul'] = 1;
    $result = JevResponseValidator::validate($response, self::questions());
    $this->assertSame(0, $result['answers']['urgency']['score']);
    $this->assertSame(1, $result['answers']['callback']['noul']);
  }

  /**
   * Probability sums tolerate 0.005 per value (+1e-9).
   *
   * @covers ::validate
   * @dataProvider providerProbabilitySum
   */
  public function testProbabilitySum(array $probabilities, bool $valid): void {
    $response = self::response();
    $response['answers']['intent']['probabilities'] = $probabilities;
    if ($valid) {
      $result = JevResponseValidator::validate($response, self::questions());
      $this->assertSame($probabilities, $result['answers']['intent']['probabilities']);
    }
    else {
      $this->assertInvalid($response);
    }
  }

  /**
   * Data provider for testProbabilitySum().
   */
  public static function providerProbabilitySum(): array {
    return [
      'exact' => [['a' => 0.6, 'b' => 0.3, 'c' => 0.1], TRUE],
      'over within tolerance' => [['a' => 0.51, 'b' => 0.5, 'c' => 0.0], TRUE],
      'under within tolerance' => [['a' => 0.5, 'b' => 0.49, 'c' => 0.0], TRUE],
      'over at tolerance' => [['a' => 0.515, 'b' => 0.5, 'c' => 0.0], TRUE],
      'over beyond tolerance' => [['a' => 0.52, 'b' => 0.5, 'c' => 0.0], FALSE],
      'under beyond tolerance' => [['a' => 0.5, 'b' => 0.48, 'c' => 0.0], FALSE],
      'all zero' => [['a' => 0, 'b' => 0, 'c' => 0], FALSE],
    ];
  }

  /**
   * Envelope failures.
   *
   * @covers ::validate
   * @dataProvider providerEnvelope
   */
  public function testEnvelope(array $response): void {
    $this->assertInvalid($response);
  }

  /**
   * Data provider for testEnvelope().
   */
  public static function providerEnvelope(): array {
    $r = self::response();
    $cases = [];
    $cases['empty'] = [[]];
    $cases['missing model'] = [array_diff_key($r, ['model' => 1])];
    foreach ([
      'empty model' => '',
      'blank model' => '  ',
      'numeric model' => 5,
      'null model' => NULL,
      'array model' => ['x'],
      '256 char model' => str_repeat('m', 256),
    ] as $label => $model) {
      $cases[$label] = [['model' => $model] + $r];
    }
    $cases['missing answers'] = [array_diff_key($r, ['answers' => 1])];
    $cases['answers string'] = [['answers' => 'x'] + $r];
    $cases['answers empty'] = [['answers' => []] + $r];
    $missing = $r;
    unset($missing['answers']['urgency']);
    $cases['missing answer'] = [$missing];
    $extra = $r;
    $extra['answers']['unknown'] = ['type' => 'noul', 'noul' => 0.1];
    $cases['extra answer'] = [$extra];
    $cases['missing usage'] = [array_diff_key($r, ['usage' => 1])];
    $cases['usage string'] = [['usage' => 'x'] + $r];
    foreach (['input_tokens', 'output_tokens'] as $field) {
      foreach ([
        'missing' => NULL,
        'negative' => -1,
        'float' => 1.0,
        'string' => '10',
        'bool' => TRUE,
      ] as $label => $value) {
        $bad = $r;
        if ($value === NULL) {
          unset($bad['usage'][$field]);
        }
        else {
          $bad['usage'][$field] = $value;
        }
        $cases["$field $label"] = [$bad];
      }
    }
    return $cases;
  }

  /**
   * Per-question failures.
   *
   * @covers ::validate
   * @dataProvider providerAnswers
   */
  public function testAnswers(string $id, array $answer): void {
    $response = self::response();
    $response['answers'][$id] = $answer;
    $this->assertInvalid($response);
  }

  /**
   * Data provider for testAnswers().
   */
  public static function providerAnswers(): array {
    $a = self::response()['answers'];
    $choice = $a['intent'];
    $score = $a['urgency'];
    $noul = $a['callback'];
    $cases = [
      'noul not array' => ['callback', [0.5]],
      'noul missing type' => ['callback', ['noul' => 0.5]],
      'noul wrong type' => ['callback', ['type' => 'choice', 'noul' => 0.5]],
      'noul missing value' => ['callback', ['type' => 'noul']],
      'noul above one' => ['callback', ['noul' => 1.5] + $noul],
      'noul negative' => ['callback', ['noul' => -0.1] + $noul],
      'noul string' => ['callback', ['noul' => '0.5'] + $noul],
      'noul bool' => ['callback', ['noul' => TRUE] + $noul],
      'noul nan' => ['callback', ['noul' => NAN] + $noul],
      'noul inf' => ['callback', ['noul' => INF] + $noul],
      'choice wrong type' => ['intent', ['type' => 'score'] + $choice],
      'choice missing confidence' => [
        'intent',
        array_diff_key($choice, ['confidence' => 1]),
      ],
      'choice confidence above one' => [
        'intent',
        ['confidence' => 1.1] + $choice,
      ],
      'choice confidence string' => [
        'intent',
        ['confidence' => '0.9'] + $choice,
      ],
      'choice missing probabilities' => [
        'intent',
        array_diff_key($choice, ['probabilities' => 1]),
      ],
      'choice probabilities missing key' => [
        'intent',
        ['probabilities' => ['a' => 0.8, 'b' => 0.2]] + $choice,
      ],
      'choice probabilities extra key' => [
        'intent',
        [
          'probabilities' => ['a' => 0.7, 'b' => 0.2, 'c' => 0.1, 'd' => 0.0],
        ] + $choice,
      ],
      'choice probability negative' => [
        'intent',
        ['probabilities' => ['a' => 1.1, 'b' => -0.1, 'c' => 0.0]] + $choice,
      ],
      'choice probability string' => [
        'intent',
        ['probabilities' => ['a' => '0.7', 'b' => 0.2, 'c' => 0.1]] + $choice,
      ],
      'choice missing choice' => [
        'intent',
        array_diff_key($choice, ['choice' => 1]),
      ],
      'choice unknown choice' => ['intent', ['choice' => 'unknown'] + $choice],
      'choice not max' => ['intent', ['choice' => 'b'] + $choice],
      'choice int' => ['intent', ['choice' => 1] + $choice],
      'score wrong type' => ['urgency', ['type' => 'noul'] + $score],
      'score missing confidence' => [
        'urgency',
        array_diff_key($score, ['confidence' => 1]),
      ],
      'score probabilities wrong length' => [
        'urgency',
        ['probabilities' => [0.2, 0.8]] + $score,
      ],
      'score probabilities assoc' => [
        'urgency',
        ['probabilities' => ['a' => 0.2, 'b' => 0.8, 'c' => 0.0]] + $score,
      ],
      'score missing' => ['urgency', array_diff_key($score, ['score' => 1])],
      'score string' => ['urgency', ['score' => '1'] + $score],
      'score negative' => ['urgency', ['score' => -0.1] + $score],
      'score above max' => ['urgency', ['score' => 2.01] + $score],
      'score far above' => ['urgency', ['score' => 99] + $score],
      'score nan' => ['urgency', ['score' => NAN] + $score],
      'score inf' => ['urgency', ['score' => INF] + $score],
      'score bool' => ['urgency', ['score' => TRUE] + $score],
      'legend missing' => ['urgency', array_diff_key($score, ['legend' => 1])],
      'legend string' => ['urgency', ['legend' => 'x'] + $score],
      'legend short' => [
        'urgency',
        ['legend' => ['Low', 'Normal']] + $score,
      ],
      'legend extra' => [
        'urgency',
        ['legend' => ['Low', 'Normal', 'High', 'X']] + $score,
      ],
      'legend non string' => [
        'urgency',
        ['legend' => ['Low', 1, 'High']] + $score,
      ],
      'legend null' => [
        'urgency',
        ['legend' => ['Low', NULL, 'High']] + $score,
      ],
    ];
    return $cases;
  }

  /**
   * Numeric choice keys need a string choice.
   *
   * @covers ::validate
   */
  public function testNumericChoiceKeys(): void {
    $questions = [
      'n' => ['type' => 'choice', 'criteria' => ['1' => 'One', '2' => 'Two']],
    ];
    $response = [
      'model' => 'm',
      'answers' => [
        'n' => [
          'type' => 'choice',
          'choice' => '2',
          'confidence' => 0.6,
          'probabilities' => [1 => 0.4, 2 => 0.6],
        ],
      ],
      'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
    ];
    $result = JevResponseValidator::validate($response, $questions);
    $this->assertSame('2', $result['answers']['n']['choice']);
    $response['answers']['n']['choice'] = 2;
    $this->assertInvalid($response, $questions);
  }

  /**
   * The legend content is not compared with the configured criteria.
   *
   * @covers ::validate
   */
  public function testLegendTextNotCompared(): void {
    $response = self::response();
    $response['answers']['urgency']['legend'] = ['a', 'b', 'c'];
    $result = JevResponseValidator::validate($response, self::questions());
    $this->assertSame(['a', 'b', 'c'], $result['answers']['urgency']['legend']);
  }

  /**
   * Envelope and usage are checked before any per-question data.
   *
   * A question lacking 'type' would raise a PHP warning if it were reached.
   *
   * @covers ::validate
   */
  public function testOrderEnvelopeBeforeQuestions(): void {
    $questions = ['broken' => []];
    $answers = ['broken' => ['type' => 'noul', 'noul' => 0.1]];
    $usage = ['input_tokens' => 1, 'output_tokens' => 1];
    $negative = ['input_tokens' => -1, 'output_tokens' => 1];
    $missing = ['input_tokens' => 1];
    $this->assertInvalid([
      'model' => '',
      'answers' => $answers,
      'usage' => $usage,
    ], $questions);
    $this->assertInvalid([
      'model' => 'm',
      'answers' => $answers,
      'usage' => $negative,
    ], $questions);
    $this->assertInvalid([
      'model' => 'm',
      'answers' => $answers,
      'usage' => $missing,
    ], $questions);
  }

  /**
   * Questions are validated in definition order, stopping at the first error.
   *
   * @covers ::validate
   */
  public function testOrderQuestionByQuestion(): void {
    $questions = [
      'first' => ['type' => 'noul'],
      'broken' => [],
    ];
    $response = [
      'model' => 'm',
      'answers' => [
        'broken' => ['type' => 'noul', 'noul' => 0.1],
        'first' => ['type' => 'noul', 'noul' => 2],
      ],
      'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
    ];
    $this->assertInvalid($response, $questions);
  }

  /**
   * Confidence is checked before probabilities and choice.
   *
   * A choice question without criteria would raise a warning if reached.
   *
   * @covers ::validate
   */
  public function testOrderConfidenceBeforeCriteria(): void {
    $questions = ['c' => ['type' => 'choice']];
    $response = [
      'model' => 'm',
      'answers' => ['c' => ['type' => 'choice', 'confidence' => 2]],
      'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
    ];
    $this->assertInvalid($response, $questions);
  }

  /**
   * Noul answers skip confidence, probabilities and criteria entirely.
   *
   * @covers ::validate
   */
  public function testNoulIgnoresCriteria(): void {
    $questions = ['n' => ['type' => 'noul']];
    $response = [
      'model' => 'm',
      'answers' => [
        'n' => [
          'type' => 'noul',
          'noul' => 0.5,
          'confidence' => 'junk',
          'probabilities' => 'junk',
        ],
      ],
      'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
    ];
    $this->assertSame([
      'answers' => ['n' => ['type' => 'noul', 'noul' => 0.5]],
      'model' => 'm',
      'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
    ], JevResponseValidator::validate($response, $questions));
  }

}
