<?php

namespace Drupal\Tests\vactory_dynamic_field\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Update 9502 and the usage of files inserted in Dynamic Fields.
 *
 * Content saved before the usage was recorded has no usage at all. The update
 * must leave it as if it had been saved with the hooks: one usage per
 * revision that references the file, as core does for formatted text fields.
 * Otherwise deleting an old revision frees a file still used by the current
 * one (Redmine #71224).
 *
 * @group vactory_dynamic_field
 */
class InlineFileUsageUpdateTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'text',
    'filter',
    'editor',
    'language',
    'entity_test',
    'vactory_dynamic_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();
    mkdir($this->siteDirectory . '/private', 0775, TRUE);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mulrev');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    foreach (['entity_test', 'entity_test_mulrev'] as $entity_type_id) {
      FieldStorageConfig::create([
        'field_name' => 'field_df',
        'entity_type' => $entity_type_id,
        'type' => 'field_wysiwyg_dynamic',
      ])->save();
      FieldConfig::create([
        'field_name' => 'field_df',
        'entity_type' => $entity_type_id,
        'bundle' => $entity_type_id,
        'translatable' => TRUE,
      ])->save();
    }
    require_once dirname(__DIR__, 3) . '/vactory_dynamic_field.install';
    // The viewer may see the test entities, not bypass file access.
    $this->setUpCurrentUser([], ['view test entity']);
  }

  /**
   * Creates a private file, as an upload from the editor does.
   */
  protected function createFile(): FileInterface {
    $uri = 'private://' . strtolower($this->randomMachineName(10)) . '.png';
    file_put_contents($uri, 'x');
    $file = File::create(['uri' => $uri, 'status' => 0]);
    $file->save();
    return $file;
  }

  /**
   * Returns a Dynamic Field value whose editor contains the files.
   */
  protected function dynamicField(FileInterface ...$files): array {
    $html = '<p>' . $this->randomMachineName() . '</p>';
    foreach ($files as $file) {
      $html .= '<img data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="/x.png">';
    }
    return [
      'widget_id' => 'vactory_default:1',
      'widget_data' => Json::encode([
        0 => ['description' => ['value' => $html, 'format' => 'full_html']],
      ]),
    ];
  }

  /**
   * Saves a new revision of an entity and returns its revision ID.
   */
  protected function saveRevision(ContentEntityInterface $entity, array $values_by_langcode): int {
    foreach ($values_by_langcode as $langcode => $value) {
      $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode, ['name' => $langcode]);
      $translation->set('field_df', $value);
    }
    $entity->setNewRevision();
    $entity->save();
    return (int) $entity->getRevisionId();
  }

  /**
   * Removes every usage, as on content saved before the usage was recorded.
   */
  protected function forgetUsages(): void {
    $this->container->get('database')->truncate('file_usage')->execute();
  }

  /**
   * Runs update 9502 until it has finished.
   */
  protected function runUpdate(): void {
    $sandbox = [];
    $passes = 0;
    do {
      vactory_dynamic_field_update_9502($sandbox);
      $this->assertLessThan(1000, ++$passes);
    } while ($sandbox['#finished'] < 1);
  }

  /**
   * Number of usages of a file by an entity, recorded for the editor module.
   */
  protected function usage(FileInterface $file, ContentEntityInterface $entity): int {
    $usage = $this->container->get('file.usage')->listUsage(File::load($file->id()));
    return (int) ($usage['editor'][$entity->getEntityTypeId()][$entity->id()] ?? 0);
  }

  /**
   * Tells whether the current user may download a private file.
   */
  protected function canDownload(FileInterface $file): bool {
    $results = $this->container->get('module_handler')->invokeAll('file_download', [$file->getFileUri()]);
    return $results && !in_array(-1, $results, TRUE);
  }

  /**
   * Creates the legacy content: 3 revisions, 2 languages, 3 files.
   *
   * - "current": every revision, in English.
   * - "translated": revisions 2 and 3, in French only.
   * - "old": revision 1 only.
   *
   * @return array
   *   The entity, the files keyed by name and the revision IDs.
   */
  protected function createContent(): array {
    $files = [
      'current' => $this->createFile(),
      'translated' => $this->createFile(),
      'old' => $this->createFile(),
    ];
    $entity = EntityTestMulRev::create([
      'name' => 'en',
      'field_df' => $this->dynamicField($files['current'], $files['old']),
    ]);
    $entity->save();
    $revisions = [(int) $entity->getRevisionId()];
    $values = [
      'en' => $this->dynamicField($files['current']),
      'fr' => $this->dynamicField($files['translated']),
    ];
    $revisions[] = $this->saveRevision($entity, $values);
    $revisions[] = $this->saveRevision($entity, $values);
    return [$entity, $files, $revisions];
  }

  /**
   * Asserts the usages of the files of createContent().
   */
  protected function assertUsages(array $expected, array $files, ContentEntityInterface $entity): void {
    $actual = [];
    foreach (array_keys($expected) as $name) {
      $actual[$name] = $this->usage($files[$name], $entity);
    }
    $this->assertSame($expected, $actual);
  }

  /**
   * The update counts one usage per revision, whatever the language.
   */
  public function testUpdateCountsEveryRevisionAndLanguage(): void {
    [$entity, $files] = $this->createContent();
    $expected = ['current' => 3, 'translated' => 2, 'old' => 1];
    // What the hooks record on content saved with them.
    $this->assertUsages($expected, $files, $entity);

    $this->forgetUsages();
    $this->runUpdate();
    $this->assertUsages($expected, $files, $entity);
    foreach ($files as $file) {
      $this->assertTrue(File::load($file->id())->isPermanent());
    }
  }

  /**
   * After the update, a file stays downloadable while a revision uses it.
   */
  public function testRevisionDeletionAfterUpdate(): void {
    [$entity, $files, $revisions] = $this->createContent();
    $this->forgetUsages();
    $this->runUpdate();
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mulrev');

    $storage->deleteRevision($revisions[0]);
    $this->assertTrue($this->canDownload($files['current']));
    $this->assertUsages(['current' => 2, 'translated' => 2, 'old' => 0], $files, $entity);

    $storage->deleteRevision($revisions[1]);
    $this->assertUsages(['current' => 1, 'translated' => 1], $files, $entity);
    $this->assertTrue($this->canDownload($files['current']));
    $this->assertTrue($this->canDownload($files['translated']));

    // A revision without the files, then the last one that has them goes.
    $this->saveRevision($entity, [
      'en' => $this->dynamicField(),
      'fr' => $this->dynamicField(),
    ]);
    $this->assertUsages(['current' => 1, 'translated' => 1], $files, $entity);
    $storage->deleteRevision($revisions[2]);
    $this->assertUsages(['current' => 0, 'translated' => 0], $files, $entity);
    $this->assertFalse($this->canDownload($files['current']));
  }

  /**
   * A file only in an old revision keeps a usage until that revision goes.
   */
  public function testFileOnlyInAnOldRevision(): void {
    $old = $this->createFile();
    $current = $this->createFile();
    $entity = EntityTestMulRev::create([
      'name' => 'en',
      'field_df' => $this->dynamicField($old),
    ]);
    $entity->save();
    $first_revision = (int) $entity->getRevisionId();
    $this->saveRevision($entity, ['en' => $this->dynamicField($current)]);
    $this->forgetUsages();

    $this->runUpdate();
    $this->assertSame(1, $this->usage($old, $entity));
    $this->assertSame(1, $this->usage($current, $entity));
    $this->assertTrue($this->canDownload($old));

    $this->container->get('entity_type.manager')->getStorage('entity_test_mulrev')->deleteRevision($first_revision);
    $this->assertSame(0, $this->usage($old, $entity));
    $this->assertSame(1, $this->usage($current, $entity));
  }

  /**
   * Running the update again, or after a save, counts nothing twice.
   */
  public function testUpdateIsIdempotent(): void {
    [$entity, $files] = $this->createContent();
    $expected = ['current' => 3, 'translated' => 2, 'old' => 1];
    $this->forgetUsages();
    $this->runUpdate();
    $this->runUpdate();
    $this->assertUsages($expected, $files, $entity);

    // A revision saved with the hooks before the update has run.
    $this->forgetUsages();
    $values = [
      'en' => $this->dynamicField($files['current']),
      'fr' => $this->dynamicField($files['translated']),
    ];
    $this->saveRevision($entity, $values);
    $this->assertUsages(['current' => 1, 'translated' => 1], $files, $entity);
    $this->runUpdate();
    $expected = ['current' => 4, 'translated' => 3, 'old' => 1];
    $this->assertUsages($expected, $files, $entity);
  }

  /**
   * The update works in passes; an entity without revisions counts once.
   */
  public function testUpdateInPasses(): void {
    $file = $this->createFile();
    $entities = [];
    for ($i = 0; $i < 5; $i++) {
      $entity = EntityTestMulRev::create([
        'name' => 'en',
        'field_df' => $this->dynamicField($file),
      ]);
      $entity->save();
      $this->saveRevision($entity, ['en' => $this->dynamicField($file)]);
      $this->saveRevision($entity, ['en' => $this->dynamicField($file)]);
      $entities[] = $entity;
    }
    $not_revisionable = EntityTest::create([
      'name' => 'en',
      'field_df' => $this->dynamicField($file),
    ]);
    $not_revisionable->save();
    $this->forgetUsages();

    $sandbox = [];
    $passes = 0;
    do {
      $recorded = $this->container->get('vactory_dynamic_field.inline_file_usage')->recordExistingContent($sandbox, 2);
      $this->assertLessThan(100, ++$passes);
    } while ($sandbox['#finished'] < 1);
    // 3 passes for 5 entities, 1 for the other entity type, 1 per field end.
    $this->assertGreaterThanOrEqual(5, $passes);
    $this->assertSame(16, $recorded);
    foreach ($entities as $entity) {
      $this->assertSame(3, $this->usage($file, $entity));
    }
    $this->assertSame(1, $this->usage($file, $not_revisionable));
  }

  /**
   * Malformed HTML neither throws nor stops the other files from counting.
   *
   * @dataProvider providerMalformedHtml
   */
  public function testMalformedHtml(string $html, array $expected): void {
    $widget_data = json_encode(['a' => ['b' => $html]], JSON_INVALID_UTF8_SUBSTITUTE);
    $service = $this->container->get('vactory_dynamic_field.inline_file_usage');
    $this->assertSame($expected, $service->parseWidgetData($widget_data));
  }

  /**
   * Data provider for testMalformedHtml().
   */
  public static function providerMalformedHtml(): array {
    $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
    $attributes = 'data-entity-type="file" data-entity-uuid="' . $uuid;
    $img = '<img ' . $attributes . '">';
    $media = '<img data-entity-type="media" data-entity-uuid="' . $uuid . '">';
    $comment = '<script><![CDATA[ data-entity-uuid ]]></script><!-- ';
    return [
      'unclosed tags' => ['<p><div>' . $img . '<span', [$uuid]],
      // Libxml recovers the tag: the file counts.
      'truncated tag' => ['<img ' . $attributes, [$uuid]],
      'binary bytes' => ["\x00\x01" . $img . "\x00", [$uuid]],
      'invalid UTF-8' => ["\xc3\x28 " . $img, [$uuid]],
      'attribute name as text' => ['data-entity-uuid', []],
      'deep nesting' => [str_repeat('<div>', 5000) . $img, [$uuid]],
      'comment and CDATA' => [$comment . $img, []],
      'not a file' => [$media, []],
    ];
  }

}
