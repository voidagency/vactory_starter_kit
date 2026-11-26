<?php

namespace Drupal\vactory_node_view_count;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * Increments the view count of a node without saving the node.
 *
 * Saving the node for each view created a revision, changed its date and
 * cleared the front-end caches. The counter is written to the field tables
 * directly, in one SQL statement, then only the node cache tag is
 * invalidated.
 */
class NodeViewCounter {

  /**
   * The view count field.
   */
  public const FIELD = 'field_node_count_view';

  /**
   * Constructs a NodeViewCounter object.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Adds one view to the node and returns the new count.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node that has the view count field.
   *
   * @return int
   *   The view count of the node, in its default language.
   */
  public function increment(NodeInterface $node): int {
    $storage = $this->entityTypeManager->getStorage('node');
    $field_storage = FieldStorageConfig::loadByName('node', self::FIELD);
    if (!$storage instanceof SqlEntityStorageInterface || !$field_storage) {
      throw new \LogicException('The view count field is not stored in SQL.');
    }
    $mapping = $storage->getTableMapping();
    $tables = [
      $mapping->getDedicatedDataTableName($field_storage) => ['entity_id' => $node->id()],
      $mapping->getDedicatedRevisionTableName($field_storage) => ['revision_id' => $node->getRevisionId()],
    ];
    $column = $mapping->getFieldColumnName($field_storage, 'value');

    $default = $node->getUntranslated();
    // A translatable count is kept per language: the default language is
    // counted, as before. Otherwise every translation holds the same value.
    $langcodes = $node->getFieldDefinition(self::FIELD)->isTranslatable()
      ? [$default->language()->getId()]
      : array_keys($node->getTranslationLanguages());

    foreach ($tables as $table => $condition) {
      $updated = $this->database->update($table)
        ->expression($column, "COALESCE($column, 0) + 1")
        ->condition(key($condition), current($condition))
        ->condition('deleted', 0)
        ->condition('delta', 0)
        ->condition('langcode', $langcodes, 'IN')
        ->execute();
      if (!$updated) {
        $this->insertFirstView($table, $column, $node, $langcodes);
      }
    }

    $storage->resetCache([$node->id()]);
    $this->cacheTagsInvalidator->invalidateTags(['node:' . $node->id()]);

    return (int) $this->database->select($mapping->getDedicatedDataTableName($field_storage), 'f')
      ->fields('f', [$column])
      ->condition('entity_id', $node->id())
      ->condition('deleted', 0)
      ->condition('delta', 0)
      ->condition('langcode', $default->language()->getId())
      ->execute()
      ->fetchField();
  }

  /**
   * Stores the first view of a node whose count was never set.
   */
  protected function insertFirstView(string $table, string $column, NodeInterface $node, array $langcodes): void {
    foreach ($langcodes as $langcode) {
      try {
        $this->database->insert($table)
          ->fields([
            'bundle' => $node->bundle(),
            'deleted' => 0,
            'entity_id' => $node->id(),
            'revision_id' => $node->getRevisionId(),
            'langcode' => $langcode,
            'delta' => 0,
            $column => 1,
          ])
          ->execute();
      }
      catch (IntegrityConstraintViolationException) {
        // Another request stored the first view in the meantime.
        $this->database->update($table)
          ->expression($column, "$column + 1")
          ->condition('revision_id', $node->getRevisionId())
          ->condition('deleted', 0)
          ->condition('delta', 0)
          ->condition('langcode', $langcode)
          ->execute();
      }
    }
  }

}
