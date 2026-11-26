<?php

namespace Drupal\vactory_node_view_count;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * Increments the view count of a node without saving the node.
 *
 * Saving the node for each view created a revision, changed its date and
 * cleared the front-end caches. The counter is written to the field tables
 * directly, in one SQL statement. The node cache tag is invalidated at most
 * once per interval and per node, so that a burst of views does not rebuild
 * the cached pages of the node on each view: the stored count is always
 * exact, a cached page shows it with at most this delay.
 */
class NodeViewCounter {

  /**
   * The view count field.
   */
  public const FIELD = 'field_node_count_view';

  /**
   * Default interval between two invalidations of a node, in seconds.
   *
   * Overridden by $settings['vactory_node_view_count_invalidation_interval'];
   * 0 invalidates on each view.
   */
  public const INVALIDATION_INTERVAL = 300;

  /**
   * Constructs a NodeViewCounter object.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected KeyValueExpirableFactoryInterface $keyValueExpirable,
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

    // Drupal stores an untranslatable field in the default language only;
    // a translatable count is kept per language and the default language is
    // counted, as before.
    $langcode = $node->getUntranslated()->language()->getId();

    foreach ($tables as $table => $condition) {
      $updated = $this->database->update($table)
        ->expression($column, "COALESCE($column, 0) + 1")
        ->condition(key($condition), current($condition))
        ->condition('deleted', 0)
        ->condition('delta', 0)
        ->condition('langcode', $langcode)
        ->execute();
      if (!$updated) {
        $this->insertFirstView($table, $column, $node, $langcode);
      }
    }

    $storage->resetCache([$node->id()]);
    $this->invalidateSpaced($node);

    return (int) $this->database->select($mapping->getDedicatedDataTableName($field_storage), 'f')
      ->fields('f', [$column])
      ->condition('entity_id', $node->id())
      ->condition('deleted', 0)
      ->condition('delta', 0)
      ->condition('langcode', $langcode)
      ->execute()
      ->fetchField();
  }

  /**
   * Invalidates the cache tag of the node, at most once per interval.
   */
  protected function invalidateSpaced(NodeInterface $node): void {
    $interval = (int) Settings::get('vactory_node_view_count_invalidation_interval', self::INVALIDATION_INTERVAL);
    if ($interval > 0) {
      $recent = $this->keyValueExpirable->get('vactory_node_view_count.invalidated');
      // Only the first view of the interval invalidates the tag.
      if (!$recent->setWithExpireIfNotExists((string) $node->id(), TRUE, $interval)) {
        return;
      }
    }
    $this->cacheTagsInvalidator->invalidateTags(['node:' . $node->id()]);
  }

  /**
   * Stores the first view of a node whose count was never set.
   */
  protected function insertFirstView(string $table, string $column, NodeInterface $node, string $langcode): void {
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
