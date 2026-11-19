<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\vactory_webform_typesafe\Form\ClassificationResultsForm;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/TypeSafeKernelTestBase.php';

/**
 * Characterizes ClassificationResultsForm::buildForm().
 *
 * @group vactory_webform_typesafe
 */
class ClassificationResultsFormTest extends TypeSafeKernelTestBase {

  /**
   * Builds the form for a Webform with the given request query.
   */
  protected function build(WebformInterface $webform, array $query = []): array {
    $request = Request::create('/results', 'GET', $query);
    $this->container->get('request_stack')->push($request);
    try {
      $form_state = new FormState();
      $form = ClassificationResultsForm::create($this->container)->buildForm([], $form_state, $webform);
      $this->assertSame($webform->id(), $form_state->get('webform_id'));
      return $form;
    }
    finally {
      $this->container->get('request_stack')->pop();
    }
  }

  /**
   * Extracts the visible classification summary of one row.
   */
  protected function summary(array $form, int $sid): array {
    $summary = $form['records']['#options'][$sid]['summary']['data'];
    $this->assertSame('container', $summary['#type']);
    $this->assertSame(['vwt-classifications'], $summary['#attributes']['class']);
    $items = [];
    foreach ($summary as $key => $item) {
      if ($key === 'empty') {
        $items['empty'] = (string) $item['#plain_text'];
      }
      if (!is_int($key)) {
        continue;
      }
      $this->assertSame(['vwt-classification'], $item['#attributes']['class']);
      $this->assertSame('span', $item['label']['#tag']);
      $this->assertSame(['vwt-label'], $item['label']['#attributes']['class']);
      $this->assertSame('strong', $item['value']['#tag']);
      $entry = [
        'label' => (string) $item['label']['text']['#plain_text'],
        'value' => (string) $item['value']['text']['#plain_text'],
      ];
      foreach (['score', 'correction'] as $extra) {
        if (isset($item[$extra])) {
          $this->assertSame('small', $item[$extra]['#tag']);
          $entry[$extra] = (string) $item[$extra]['text']['#plain_text'];
        }
      }
      $items[] = $entry;
    }
    return $items;
  }

  /**
   * Extracts a badge as [text, classes].
   */
  protected function badge(array $form, int $sid, string $column): array {
    $badge = $form['records']['#options'][$sid][$column]['data'];
    $this->assertSame('html_tag', $badge['#type']);
    $this->assertSame('span', $badge['#tag']);
    return [
      (string) $badge['text']['#plain_text'],
      $badge['#attributes']['class'],
    ];
  }

  /**
   * Extracts the preview of one row as label => excerpt, plus the link.
   */
  protected function preview(array $form, int $sid, string $wid = 'typesafe_test'): array {
    $preview = $form['records']['#options'][$sid]['id']['data'];
    $this->assertSame(['vwt-submission'], $preview['#attributes']['class']);
    $link = $preview['link'];
    $this->assertSame('link', $link['#type']);
    $this->assertSame('Soumission #' . $sid, (string) $link['#title']);
    $this->assertSame('vactory_webform_typesafe.analysis', $link['#url']->getRouteName());
    $this->assertSame([
      'webform' => $wid,
      'webform_submission' => $sid,
    ], $link['#url']->getRouteParameters());
    $fields = [];
    foreach ($preview as $key => $item) {
      if (is_int($key)) {
        $this->assertSame('p', $item['excerpt']['#tag']);
        $fields[(string) $item['label']['text']['#plain_text']] = (string) $item['excerpt']['text']['#plain_text'];
      }
    }
    if (isset($preview['empty'])) {
      $fields['#empty'] = (string) $preview['empty']['#plain_text'];
    }
    return $fields;
  }

  /**
   * Filters, defaults from the query string and the pending action.
   */
  public function testFiltersAndPendingAction(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $form = $this->build($webform, [
      'status' => 'failed',
      'review' => 'reviewed',
      'question' => 'urgency',
      'value' => '2',
    ]);
    $this->assertSame(0, $form['#cache']['max-age']);
    $this->assertSame(['vactory_webform_typesafe/results'], $form['#attached']['library']);
    $this->assertSame('details', $form['filters']['#type']);
    $this->assertTrue($form['filters']['#open']);
    $this->assertSame([
      '',
      'pending',
      'processing',
      'completed',
      'failed',
      'stale',
    ], array_keys($form['filters']['status']['#options']));
    $this->assertSame('Outdated', (string) $form['filters']['status']['#options']['stale']);
    $this->assertSame('failed', $form['filters']['status']['#default_value']);
    $this->assertSame(['', 'needs_review', 'unreviewed', 'reviewed'], array_keys($form['filters']['review']['#options']));
    $this->assertSame('reviewed', $form['filters']['review']['#default_value']);
    $this->assertSame([
      '' => 'Any question',
      'intent' => 'Objet principal de la demande',
      'sentiment' => 'Sentiment exprimé',
      'urgency' => 'Degré d’urgence',
      'callback' => 'Rappel téléphonique demandé',
    ], array_map('strval', $form['filters']['question']['#options']));
    $this->assertSame('urgency', $form['filters']['question']['#default_value']);
    $this->assertSame('textfield', $form['filters']['value']['#type']);
    $this->assertSame('2', $form['filters']['value']['#default_value']);
    $this->assertSame('submit', $form['filters']['apply']['#type']);
    $this->assertSame(['::filterResults'], $form['filters']['apply']['#submit']);
    $this->assertSame([['status'], ['review'], ['question'], ['value']], $form['filters']['apply']['#limit_validation_errors']);

    $this->assertSame('submit', $form['process_pending']['#type']);
    $this->assertSame('Traiter les soumissions en attente', (string) $form['process_pending']['#value']);
    $this->assertSame(['::processPending'], $form['process_pending']['#submit']);
    $this->assertSame([], $form['process_pending']['#limit_validation_errors']);
    $this->assertStringStartsWith('<p>Traitez immédiatement toutes les soumissions en attente', (string) $form['pending_help']['#markup']);
    $this->assertStringEndsWith('</p>', (string) $form['pending_help']['#markup']);
    $this->assertTrue($form['actions']['analyze']['#access']);
    $this->assertSame('Réanalyser les soumissions sélectionnées', (string) $form['actions']['analyze']['#value']);
    $this->assertSame('pager', $form['pager']['#type']);
    $this->assertArrayHasKey('note', $form);
    $this->assertArrayNotHasKey('backfill', $form);

    // Empty state.
    $this->assertSame('tableselect', $form['records']['#type']);
    $this->assertSame(['vwt-results'], $form['records']['#attributes']['class']);
    $this->assertSame([
      'id' => 'Soumission et contenu',
      'status' => 'État',
      'review' => 'Vérification',
      'summary' => 'Classification',
    ], array_map('strval', $form['records']['#header']));
    $this->assertSame([], $form['records']['#options']);
    $this->assertSame('Aucune analyse correspondante.', (string) $form['records']['#empty']);
  }

  /**
   * Without the run permission the pending and reanalyze actions are hidden.
   */
  public function testWithoutRunPermission(): void {
    $this->loginWith([
      'view any webform submission',
      'view vactory webform typesafe',
    ]);
    $webform = $this->createTypeSafeWebform();
    $form = $this->build($webform);
    $this->assertArrayNotHasKey('process_pending', $form);
    $this->assertArrayNotHasKey('pending_help', $form);
    $this->assertFalse($form['actions']['analyze']['#access']);
    $this->assertSame('', $form['filters']['status']['#default_value']);
    $this->assertSame('', $form['filters']['question']['#default_value']);
  }

  /**
   * Rows: summary, overrides, built-in urgency labels, badges and preview.
   */
  public function testRows(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $other = $this->createTypeSafeWebform('typesafe_other');
    $long = str_repeat('mot ', 60);
    $s1 = $this->createSubmission($webform, [
      'subject' => "  Hello\n\n   world ",
      'message' => $long,
      'secret' => 'never shown',
    ]);
    $this->createRecord($s1, ['overrides' => json_encode(['sentiment' => 'frustrated'])]);
    $s2 = $this->createSubmission($webform, ['message' => 'Pending one']);
    $this->createRecord($s2, ['status' => 'pending']);
    $s3 = $this->createSubmission($webform, ['secret' => 'only a secret']);
    $this->createRecord($s3, ['review_status' => 'needs_review']);
    $s4 = $this->createSubmission($webform, ['message' => 'x']);
    $this->createRecord($s4, [
      'status' => 'failed',
      'review_status' => 'needs_review',
    ]);
    $s5 = $this->createSubmission($webform, ['message' => 'x']);
    $this->createRecord($s5, [
      'status' => 'stale',
      'review_status' => 'reviewed',
    ]);
    $s6 = $this->createSubmission($webform, ['message' => 'x']);
    $this->createRecord($s6, ['status' => 'processing']);
    $s7 = $this->createSubmission($webform, ['message' => 'x']);
    $this->createRecord($s7, ['status' => 'weird', 'review_status' => 'odd']);
    // Another Webform's record is never listed.
    $s8 = $this->createSubmission($other, ['message' => 'x']);
    $this->createRecord($s8);
    // A submission without analysis is not listed either.
    $this->createSubmission($webform, ['message' => 'no analysis']);

    $form = $this->build($webform);
    $ordered = [$s7, $s6, $s5, $s4, $s3, $s2, $s1];
    $sids = array_map(static fn($s) => (int) $s->id(), $ordered);
    $this->assertSame($sids, array_keys($form['records']['#options']));
    $this->assertSame(['id', 'status', 'review', 'summary'], array_keys($form['records']['#options'][$sids[0]]));

    $id1 = (int) $s1->id();
    $this->assertSame([
      [
        'label' => 'Objet principal de la demande',
        'value' => 'Demande de remboursement',
      ],
      [
        'label' => 'Sentiment exprimé',
        'value' => 'Frustration',
        'correction' => 'Corrigé manuellement',
      ],
      [
        'label' => 'Degré d’urgence',
        'value' => 'Urgence normale à élevée',
        'score' => 'Score : 1,5 / 2',
      ],
      [
        'label' => 'Rappel téléphonique demandé',
        'value' => 'Oui',
      ],
    ], $this->summary($form, $id1));
    $preview = $this->preview($form, $id1);
    $this->assertSame(['Subject', 'Message'], array_keys($preview));
    $this->assertSame('Hello world', $preview['Subject']);
    $this->assertLessThanOrEqual(180, mb_strlen($preview['Message']));
    $this->assertStringEndsWith('…', $preview['Message']);
    $this->assertStringStartsWith('mot mot', $preview['Message']);
    $this->assertSame(['Terminée', ['vwt-badge', 'vwt-badge--success']], $this->badge($form, $id1, 'status'));
    $this->assertSame(['Non vérifié', ['vwt-badge', 'vwt-badge--neutral']], $this->badge($form, $id1, 'review'));

    $id2 = (int) $s2->id();
    $this->assertSame(['empty' => 'Classification indisponible pour le moment.'], $this->summary($form, $id2));
    $this->assertSame(['Message' => 'Pending one'], $this->preview($form, $id2));
    $this->assertSame(['En attente', ['vwt-badge', 'vwt-badge--neutral']], $this->badge($form, $id2, 'status'));

    $id3 = (int) $s3->id();
    $this->assertCount(4, $this->summary($form, $id3));
    $this->assertSame(['#empty' => 'Aucun contenu à afficher dans les champs sélectionnés.'], $this->preview($form, $id3));
    $this->assertSame(['À vérifier', ['vwt-badge', 'vwt-badge--warning']], $this->badge($form, $id3, 'review'));

    $id4 = (int) $s4->id();
    $this->assertSame(['empty' => 'Classification indisponible pour le moment.'], $this->summary($form, $id4));
    $this->assertSame(['En échec', ['vwt-badge', 'vwt-badge--error']], $this->badge($form, $id4, 'status'));

    $id5 = (int) $s5->id();
    $this->assertSame(['À actualiser', ['vwt-badge', 'vwt-badge--warning']], $this->badge($form, $id5, 'status'));
    $this->assertSame(['Vérifié', ['vwt-badge', 'vwt-badge--success']], $this->badge($form, $id5, 'review'));

    $this->assertSame(['En cours', ['vwt-badge', 'vwt-badge--neutral']], $this->badge($form, (int) $s6->id(), 'status'));
    $id7 = (int) $s7->id();
    $this->assertSame(['weird', ['vwt-badge', 'vwt-badge--neutral']], $this->badge($form, $id7, 'status'));
    $this->assertSame(['odd', ['vwt-badge', 'vwt-badge--neutral']], $this->badge($form, $id7, 'review'));
  }

  /**
   * Score display and the built-in urgency scale labels.
   *
   * @dataProvider providerScores
   */
  public function testScoreDisplay(string $id, ?array $criteria, float $score, ?string $override, array $expected): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $submission = $this->createSubmission($webform, ['message' => 'x']);
    $question = QuestionDefinition::defaults()['urgency'];
    if ($criteria !== NULL) {
      $question['criteria'] = $criteria;
    }
    $this->createRecord($submission, [
      'questions' => json_encode([$id => $question]),
      'results' => json_encode([
        'answers' => [
          $id => [
            'type' => 'score',
            'score' => $score,
            'confidence' => 1.0,
            'probabilities' => [],
          ],
          // Answers without a stored question are not shown.
          'unknown' => ['type' => 'noul', 'noul' => 1.0],
        ],
      ]),
      'overrides' => json_encode($override === NULL ? [] : [$id => $override]),
    ]);
    $form = $this->build($webform);
    $this->assertSame([['label' => 'Degré d’urgence'] + $expected], $this->summary($form, (int) $submission->id()));
  }

  /**
   * Provides score display cases.
   */
  public static function providerScores(): array {
    $english = [
      'Low: no deadline or immediate impact',
      'Normal: routine request',
      'High: explicit near deadline or significant current disruption',
    ];
    return [
      'zero' => [
        'urgency',
        NULL,
        0.0,
        NULL,
        [
          'value' => 'Urgence faible',
          'score' => 'Score : 0 / 2',
        ],
      ],
      'below one' => [
        'urgency',
        NULL,
        0.4,
        NULL,
        [
          'value' => 'Urgence faible à normale',
          'score' => 'Score : 0,4 / 2',
        ],
      ],
      'one' => [
        'urgency',
        NULL,
        1.0,
        NULL,
        [
          'value' => 'Urgence normale',
          'score' => 'Score : 1 / 2',
        ],
      ],
      'below two' => [
        'urgency',
        NULL,
        1.99,
        NULL,
        [
          'value' => 'Urgence normale à élevée',
          'score' => 'Score : 1,99 / 2',
        ],
      ],
      'two' => [
        'urgency',
        NULL,
        2.0,
        NULL,
        [
          'value' => 'Urgence élevée',
          'score' => 'Score : 2 / 2',
        ],
      ],
      'english legacy scale' => [
        'urgency',
        $english,
        0.5,
        NULL,
        [
          'value' => 'Urgence faible à normale',
          'score' => 'Score : 0,5 / 2',
        ],
      ],
      'override' => [
        'urgency',
        NULL,
        0.0,
        '2',
        [
          'value' => 'Urgence élevée',
          'score' => 'Score : 2 / 2',
          'correction' => 'Corrigé manuellement',
        ],
      ],
      'custom scale' => [
        'urgency',
        ['a', 'b', 'c', 'd'],
        1.5,
        NULL,
        [
          'value' => '1,5 / 3',
        ],
      ],
      'other id with built-in scale' => [
        'priority',
        NULL,
        1.5,
        NULL,
        [
          'value' => '1,5 / 2',
        ],
      ],
    ];
  }

  /**
   * Status, review and classification filters from the query string.
   *
   * @dataProvider providerFilters
   */
  public function testFilters(array $query, array $expected): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $sids = [];
    foreach ([
      'a' => [
        'status' => 'completed',
        'review_status' => 'reviewed',
        'labels' => ['intent:refund', 'urgency:2'],
      ],
      'b' => [
        'status' => 'completed',
        'review_status' => 'unreviewed',
        'labels' => ['intent:complaint', 'urgency:1.5'],
      ],
      'c' => [
        'status' => 'failed',
        'review_status' => 'unreviewed',
        'labels' => [],
      ],
    ] as $name => $values) {
      $submission = $this->createSubmission($webform, ['message' => $name]);
      $this->createRecord($submission, $values);
      $sids[(int) $submission->id()] = $name;
    }
    $form = $this->build($webform, $query);
    $this->assertSame($expected, array_values(array_map(static fn($sid) => $sids[$sid], array_keys($form['records']['#options']))));
  }

  /**
   * Provides filter cases.
   */
  public static function providerFilters(): array {
    return [
      'none' => [[], ['c', 'b', 'a']],
      'status' => [['status' => 'completed'], ['b', 'a']],
      'unknown status ignored' => [['status' => 'bogus'], ['c', 'b', 'a']],
      'review' => [['review' => 'unreviewed'], ['c', 'b']],
      'status and review' => [
        ['status' => 'completed', 'review' => 'reviewed'],
        ['a'],
      ],
      'question value trimmed' => [
        ['question' => 'intent', 'value' => ' refund '],
        ['a'],
      ],
      'numeric score value' => [
        ['question' => 'urgency', 'value' => '1.5'],
        ['b'],
      ],
      'value without question ignored' => [
        ['value' => 'refund'],
        ['c', 'b', 'a'],
      ],
      'unknown question ignored' => [
        ['question' => 'nope', 'value' => 'refund'],
        ['c', 'b', 'a'],
      ],
      'blank value ignored' => [
        ['question' => 'intent', 'value' => '  '],
        ['c', 'b', 'a'],
      ],
      'no match' => [['question' => 'intent', 'value' => 'other'], []],
    ];
  }

  /**
   * Invalid handler questions: no question filter, rows still listed.
   */
  public function testInvalidHandlerQuestions(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform('typesafe_test', '{}');
    $submission = $this->createSubmission($webform, ['message' => 'Body']);
    $this->createRecord($submission, ['labels' => ['intent:complaint']]);
    $form = $this->build($webform, ['question' => 'intent', 'value' => 'refund']);
    $this->assertSame(['' => 'Any question'], array_map('strval', $form['filters']['question']['#options']));
    $this->assertSame('intent', $form['filters']['question']['#default_value']);
    $sid = (int) $submission->id();
    $this->assertSame([$sid], array_keys($form['records']['#options']));
    // Stored questions are still used for the summary.
    $this->assertCount(4, $this->summary($form, $sid));
    // Handler fields still drive the preview.
    $this->assertSame(['Message' => 'Body'], $this->preview($form, $sid));
    $this->assertArrayHasKey('process_pending', $form);

    // Malformed JSON behaves the same.
    $webform = $this->createTypeSafeWebform('typesafe_broken', 'not json');
    $this->assertSame(['' => 'Any question'], array_map('strval', $this->build($webform)['filters']['question']['#options']));
  }

  /**
   * Without an enabled handler, no question options and empty previews.
   */
  public function testWithoutEnabledHandler(): void {
    $this->loginWith();
    $cases = [
      'typesafe_disabled' => [TRUE, FALSE],
      'typesafe_none' => [FALSE, TRUE],
    ];
    foreach ($cases as $id => [$handler, $enabled]) {
      $webform = $this->createTypeSafeWebform($id, NULL, [], $enabled, $handler);
      $submission = $this->createSubmission($webform, ['message' => 'Body']);
      $this->createRecord($submission);
      $form = $this->build($webform);
      $this->assertSame(['' => 'Any question'], array_map('strval', $form['filters']['question']['#options']));
      $sid = (int) $submission->id();
      $this->assertCount(4, $this->summary($form, $sid));
      $this->assertSame(['#empty' => 'Aucun contenu à afficher dans les champs sélectionnés.'], $this->preview($form, $sid, $id));
    }
  }

  /**
   * Rows of submissions the user cannot view are skipped.
   */
  public function testRowsWithoutSubmissionAccess(): void {
    $this->loginWith([
      'view vactory webform typesafe',
      'run vactory webform typesafe',
    ]);
    $webform = $this->createTypeSafeWebform();
    $submission = $this->createSubmission($webform, ['message' => 'Body']);
    $this->createRecord($submission);
    $form = $this->build($webform);
    $this->assertSame([], $form['records']['#options']);
  }

  /**
   * Records are paged by 25.
   */
  public function testPager(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $sids = [];
    for ($i = 0; $i < 26; $i++) {
      $submission = $this->createSubmission($webform, ['message' => 'm' . $i]);
      $this->createRecord($submission, ['status' => 'pending']);
      $sids[] = (int) $submission->id();
    }
    // The pager service keeps its first state, so only one page per test.
    $form = $this->build($webform, ['page' => '1']);
    $this->assertSame([$sids[0]], array_keys($form['records']['#options']));
  }

  /**
   * The first page holds the 25 newest records.
   */
  public function testPagerFirstPage(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform();
    $sids = [];
    for ($i = 0; $i < 26; $i++) {
      $submission = $this->createSubmission($webform, ['message' => 'm' . $i]);
      $this->createRecord($submission, ['status' => 'pending']);
      $sids[] = (int) $submission->id();
    }
    $form = $this->build($webform);
    $this->assertSame(array_slice(array_reverse($sids), 0, 25), array_keys($form['records']['#options']));
  }

}
