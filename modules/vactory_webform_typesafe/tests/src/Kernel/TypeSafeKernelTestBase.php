<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\vactory_webform_typesafe\Service\AnalysisManager;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;
use Drupal\vactory_webform_typesafe\Service\TypeSafeClassifierInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Shared fixtures for vactory_webform_typesafe kernel tests.
 */
abstract class TypeSafeKernelTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'field',
    'webform',
    'vactory_webform_typesafe',
  ];

  /**
   * Permissions that grant every TypeSafe and submission capability.
   */
  protected const ALL_PERMISSIONS = [
    'administer webform',
    'view any webform submission',
    'view vactory webform typesafe',
    'run vactory webform typesafe',
    'review vactory webform typesafe',
  ];

  /**
   * Classifier stub that must never be called by the code under test.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\TypeSafeClassifierInterface
   */
  protected $classifier;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['system', 'webform', 'vactory_webform_typesafe']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('vactory_webform_analysis');
    // Reserve uid 0 and the uid 1 super user so test users get real checks.
    User::create(['uid' => 0, 'name' => '', 'status' => 0])->save();
    User::create(['uid' => 1, 'name' => 'root', 'status' => 1])->save();
    $this->classifier = new class() implements TypeSafeClassifierInterface {

      /**
       * Number of classify() calls.
       *
       * @var int
       */
      public int $calls = 0;

      /**
       * Models returned by getModels().
       *
       * @var array
       */
      public array $models = ['jev-latest'];

      /**
       * {@inheritdoc}
       */
      public function classify(array $state, array $questions, string $model): array {
        $this->calls++;
        throw new \LogicException('The classifier must not be called.');
      }

      /**
       * Lists models without any network access.
       */
      public function getModels(): array {
        return $this->models;
      }

    };
    $this->container->set('vactory_webform_typesafe.classifier', $this->classifier);
  }

  /**
   * Creates and logs in a user with the given permissions.
   */
  protected function loginWith(array $permissions = self::ALL_PERMISSIONS): User {
    $account = $this->createUser($permissions);
    $this->setCurrentUser($account);
    return $account;
  }

  /**
   * Returns the analysis manager service.
   */
  protected function manager(): AnalysisManager {
    return $this->container->get('vactory_webform_typesafe.manager');
  }

  /**
   * Creates a Webform with a TypeSafe classification handler.
   */
  protected function createTypeSafeWebform(string $id = 'typesafe_test', ?string $questions = NULL, array $settings = [], bool $enabled = TRUE, bool $handler = TRUE): WebformInterface {
    $webform = Webform::create([
      'id' => $id,
      'title' => 'TypeSafe test ' . $id,
      'status' => 'open',
    ]);
    $webform->setElements([
      'subject' => ['#type' => 'textfield', '#title' => 'Subject'],
      'message' => ['#type' => 'textarea', '#title' => 'Message'],
      'secret' => ['#type' => 'password', '#title' => 'Secret'],
    ]);
    $webform->save();
    if ($handler) {
      $instance = $this->container->get('plugin.manager.webform.handler')->createInstance('vactory_typesafe', [
        'handler_id' => 'classification',
        'status' => $enabled,
        'settings' => $settings + [
          'fields' => ['subject', 'message'],
          'automatic' => FALSE,
          'on_update' => FALSE,
          'model' => '',
          'questions' => $questions ?? json_encode(QuestionDefinition::defaults()),
        ],
      ]);
      $instance->setWebform($webform);
      $webform->addWebformHandler($instance);
      $webform->save();
    }
    return $webform;
  }

  /**
   * Creates a completed (or draft) submission.
   */
  protected function createSubmission(WebformInterface $webform, array $data, array $values = []): WebformSubmissionInterface {
    $submission = WebformSubmission::create($values + [
      'webform_id' => $webform->id(),
      'data' => $data,
    ]);
    $submission->save();
    return $submission;
  }

  /**
   * Creates an analysis record directly in storage.
   */
  protected function createRecord(WebformSubmissionInterface $submission, array $values = []) {
    $sid = (int) $submission->id();
    $record = $this->container->get('entity_type.manager')->getStorage('vactory_webform_analysis')->create($values + [
      'id' => $sid,
      'submission' => $sid,
      'webform' => $submission->getWebform()->id(),
      'status' => 'completed',
      'review_status' => 'unreviewed',
      'fingerprint' => 'old-fingerprint',
      'generation' => 'gen-' . $sid,
      'model' => 'test-model',
      'questions' => json_encode(QuestionDefinition::defaults()),
      'results' => json_encode([
        'model' => 'test-model',
        'answers' => $this->defaultAnswers(),
      ]),
      'overrides' => '{}',
      'attempts' => 1,
      'error' => '',
    ]);
    $record->save();
    return $record;
  }

  /**
   * Returns a valid answer set for the default questions.
   */
  protected function defaultAnswers(): array {
    return [
      'intent' => [
        'type' => 'choice',
        'choice' => 'refund',
        'confidence' => 0.9,
        'probabilities' => ['refund' => 0.9, 'complaint' => 0.1],
      ],
      'sentiment' => [
        'type' => 'choice',
        'choice' => 'angry',
        'confidence' => 0.6,
        'probabilities' => ['angry' => 0.6, 'frustrated' => 0.4],
      ],
      'urgency' => [
        'type' => 'score',
        'score' => 1.5,
        'confidence' => 0.8,
        'probabilities' => [0.1, 0.3, 0.6],
      ],
      'callback' => [
        'type' => 'noul',
        'noul' => 0.725,
      ],
    ];
  }

  /**
   * Returns the submission IDs currently in the TypeSafe queue.
   */
  protected function queuedSids(): array {
    if (!$this->container->get('database')->schema()->tableExists('queue')) {
      return [];
    }
    $rows = $this->container->get('database')->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('name', AnalysisManager::QUEUE)
      ->orderBy('item_id')
      ->execute()->fetchCol();
    return array_map(static fn($data) => (int) unserialize($data, ['allowed_classes' => FALSE])['sid'], $rows);
  }

  /**
   * Returns rendered messenger messages of one type as strings.
   */
  protected function messages(string $type): array {
    return array_map('strval', $this->container->get('messenger')->messagesByType($type));
  }

}
