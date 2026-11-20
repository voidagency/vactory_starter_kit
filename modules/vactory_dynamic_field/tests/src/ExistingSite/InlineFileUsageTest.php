<?php

namespace Drupal\Tests\vactory_dynamic_field\ExistingSite;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Files inserted in the editors of a Dynamic Field are recorded as used.
 *
 * An image uploaded from CKEditor in a Dynamic Field used to stay a temporary
 * file without usage (Redmine #71224).
 *
 * @group vactory_dynamic_field
 */
class InlineFileUsageTest extends ExistingSiteBase {

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileUsage = $this->container->get('file.usage');
  }

  /**
   * Creates a temporary file, as an upload from the editor does.
   */
  protected function createInlineFile(): FileInterface {
    $uri = 'public://df-inline-' . strtolower($this->randomMachineName(10)) . '.png';
    file_put_contents($uri, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    $file = File::create(['uri' => $uri, 'status' => 0]);
    $file->save();
    $this->markEntityForCleanup($file);
    $this->assertTrue($file->isTemporary());
    return $file;
  }

  /**
   * Returns the HTML of an editor with the files inserted.
   */
  protected function html(FileInterface ...$files): array {
    $html = '<p>Text</p>';
    foreach ($files as $file) {
      $html .= '<img data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="/sites/default/files/x.png" alt="">';
    }
    return ['value' => $html, 'format' => 'full_html'];
  }

  /**
   * Creates a block with a Dynamic Field, without saving it.
   */
  protected function createBlock(array $widget_data): BlockContent {
    $block = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Inline file usage ' . $this->randomMachineName(8),
      'field_dynamic_block_components' => [
        'widget_id' => 'vactory_default:1',
        'widget_data' => Json::encode($widget_data),
      ],
    ]);
    $this->markEntityForCleanup($block);
    return $block;
  }

  /**
   * Sets the widget data of a block.
   */
  protected function setWidgetData(BlockContent $block, array $widget_data): void {
    $block->set('field_dynamic_block_components', [
      'widget_id' => 'vactory_default:1',
      'widget_data' => Json::encode($widget_data),
    ]);
  }

  /**
   * Number of usages of a file by a block, recorded for the editor module.
   */
  protected function usageCount(FileInterface $file, BlockContent $block): int {
    $usage = $this->fileUsage->listUsage($file);
    return (int) ($usage['editor']['block_content'][$block->id()] ?? 0);
  }

  /**
   * Reloads a file and tells whether it is permanent.
   */
  protected function isPermanent(FileInterface $file): bool {
    $storage = $this->container->get('entity_type.manager')->getStorage('file');
    $storage->resetCache([$file->id()]);
    return $storage->load($file->id())->isPermanent();
  }

  /**
   * Files of simple fields, groups, multiple items and extra fields.
   */
  public function testInsertRecordsEveryEditor(): void {
    $simple = $this->createInlineFile();
    $group = $this->createInlineFile();
    $second_item = $this->createInlineFile();
    $extra = $this->createInlineFile();
    $unused = $this->createInlineFile();

    $block = $this->createBlock([
      0 => [
        'title' => 'First',
        'description' => $this->html($simple),
        'group' => ['text' => $this->html($group), 'label' => 'x'],
      ],
      1 => [
        'title' => 'Second',
        // The same file twice in an item counts once.
        'description' => $this->html($second_item, $second_item),
      ],
      'extra_field' => [
        'intro' => $this->html($extra),
        'group' => ['outro' => $this->html($simple)],
      ],
    ]);
    $block->save();

    foreach ([$simple, $group, $second_item, $extra] as $file) {
      $this->assertTrue($this->isPermanent($file));
      $this->assertSame(1, $this->usageCount($file, $block));
    }
    $this->assertFalse($this->isPermanent($unused));
    $this->assertSame(0, $this->usageCount($unused, $block));
  }

  /**
   * Saving the same revision adds the new files and frees the removed ones.
   */
  public function testRevisionUpdate(): void {
    $kept = $this->createInlineFile();
    $removed = $this->createInlineFile();
    $added = $this->createInlineFile();
    $block = $this->createBlock([0 => ['description' => $this->html($kept, $removed)]]);
    $block->save();
    $this->assertSame(1, $this->usageCount($removed, $block));

    $this->setWidgetData($block, [0 => ['description' => $this->html($kept, $added)]]);
    $block->setNewRevision(FALSE);
    $block->save();

    $this->assertSame(1, $this->usageCount($kept, $block));
    $this->assertSame(0, $this->usageCount($removed, $block));
    $this->assertSame(1, $this->usageCount($added, $block));
    $this->assertTrue($this->isPermanent($added));
  }

  /**
   * Each revision counts; deleting a revision or the entity frees the files.
   */
  public function testRevisionsAndDelete(): void {
    $file = $this->createInlineFile();
    $block = $this->createBlock([0 => ['description' => $this->html($file)]]);
    $block->save();
    $first_revision = $block->getRevisionId();

    $block->setNewRevision(TRUE);
    $block->save();
    $this->assertSame(2, $this->usageCount($file, $block));

    $this->container->get('entity_type.manager')->getStorage('block_content')->deleteRevision($first_revision);
    $this->assertSame(1, $this->usageCount($file, $block));

    $block->delete();
    $this->assertSame(0, $this->usageCount($file, $block));
  }

  /**
   * The update records the files of content saved before the fix.
   */
  public function testUpdateRecordsExistingContent(): void {
    $file = $this->createInlineFile();
    $block = $this->createBlock([0 => ['description' => $this->html($file)]]);
    $block->save();
    // Back to the state left by the previous versions.
    $this->fileUsage->delete($file, 'editor', 'block_content', $block->id(), 0);
    $file->setTemporary();
    $file->save();
    $this->assertSame(0, $this->usageCount($file, $block));

    require_once dirname(__DIR__, 3) . '/vactory_dynamic_field.install';
    for ($run = 0; $run < 2; $run++) {
      $sandbox = [];
      $passes = 0;
      do {
        vactory_dynamic_field_update_9502($sandbox);
        $this->assertLessThan(10000, ++$passes);
      } while ($sandbox['#finished'] < 1);
      // Running the update twice does not count the file twice.
      $this->assertTrue($this->isPermanent($file));
      $this->assertSame(1, $this->usageCount($file, $block));
    }
  }

}
