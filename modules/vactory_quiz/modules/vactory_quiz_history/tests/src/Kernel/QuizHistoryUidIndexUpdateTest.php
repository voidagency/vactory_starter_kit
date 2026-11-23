<?php

namespace Drupal\Tests\vactory_quiz_history\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the index on the user ID of the quiz history (Redmine #71440).
 *
 * @group vactory_quiz_history
 */
class QuizHistoryUidIndexUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'vactory_quiz_history'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('vactory_quiz_history', ['vactory_quiz_history']);
    $this->container->get('module_handler')->loadInclude('vactory_quiz_history', 'install');
  }

  /**
   * A new site gets the index from the schema.
   */
  public function testNewInstallHasTheIndex(): void {
    $schema = $this->container->get('database')->schema();
    $this->assertTrue($schema->indexExists('vactory_quiz_history', 'uid'));
  }

  /**
   * The update adds the index to an existing table and keeps its rows.
   */
  public function testUpdateAddsTheIndexOnce(): void {
    $database = $this->container->get('database');
    $schema = $database->schema();
    // An existing site: the table was created without the index.
    $schema->dropIndex('vactory_quiz_history', 'uid');
    $this->assertFalse($schema->indexExists('vactory_quiz_history', 'uid'));
    $row = [
      'uid' => 7,
      'quiz_nid' => 1,
      'user_mark' => 10,
      'perfect_mark' => 10,
      'user_answers' => '[]',
      'time' => 1,
      'certificat' => 'private://quiz_certificat/2026-11/a.pdf',
    ];
    $database->insert('vactory_quiz_history')->fields($row)->execute();

    vactory_quiz_history_update_10001();
    $this->assertTrue($schema->indexExists('vactory_quiz_history', 'uid'));

    // A second run changes nothing and does not fail.
    vactory_quiz_history_update_10001();
    $this->assertTrue($schema->indexExists('vactory_quiz_history', 'uid'));

    $stored = $database->select('vactory_quiz_history', 'h')
      ->fields('h', array_keys($row))
      ->condition('h.uid', 7)
      ->execute()
      ->fetchAssoc();
    $this->assertEquals($row, $stored);
  }

  /**
   * The update does nothing when the table is missing.
   */
  public function testUpdateWithoutTheTable(): void {
    $schema = $this->container->get('database')->schema();
    $schema->dropTable('vactory_quiz_history');
    vactory_quiz_history_update_10001();
    $this->assertFalse($schema->tableExists('vactory_quiz_history'));
  }

}
