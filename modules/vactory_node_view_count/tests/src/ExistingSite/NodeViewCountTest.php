<?php

namespace Drupal\Tests\vactory_node_view_count\ExistingSite;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * View count of a node, sent by an anonymous visitor over HTTP.
 *
 * @group vactory_node_view_count
 */
class NodeViewCountTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['vactory_node_view_count'];

  /**
   * Bundle counted during the test.
   */
  private const BUNDLE = 'vactory_page';

  /**
   * The view count field.
   */
  private const FIELD = 'field_node_count_view';

  /**
   * Views counted per node and per IP address within a minute.
   */
  private const FLOOD_THRESHOLD = 20;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->modifyConfigValue('node.type.' . self::BUNDLE, 'third_party_settings.vactory_node_view_count.enabling_count_node', TRUE);
    if (!FieldConfig::loadByName('node', self::BUNDLE, self::FIELD)) {
      $had_storage = (bool) FieldStorageConfig::loadByName('node', self::FIELD);
      \Drupal::service('vactory_node_view_count.node_view_count')->createNodeViewCountField(self::BUNDLE, self::FIELD);
      $field = FieldConfig::loadByName('node', self::BUNDLE, self::FIELD);
      $this->markEntityForCleanup($had_storage ? $field : $field->getFieldStorageDefinition());
    }
  }

  /**
   * A view is counted without saving the node.
   */
  public function testViewIsCountedWithoutSave(): void {
    $node = $this->createNode([
      'type' => self::BUNDLE,
      'moderation_state' => 'published',
    ]);
    $changed = $node->getChangedTime();
    $revisions = $this->revisionCount($node);

    foreach ([1, 2, 3] as $expected) {
      [$status, $body] = $this->put($node->id());
      $this->assertSame(200, $status);
      $this->assertSame($expected, $body['count']);
    }

    $reloaded = $this->reload($node);
    $this->assertSame(3, (int) $reloaded->get(self::FIELD)->value);
    // No save: same changed date, no new revision, so no entity update hook
    // and no front-end cache clear.
    $this->assertSame($changed, $reloaded->getChangedTime());
    $this->assertSame($revisions, $this->revisionCount($reloaded));
  }

  /**
   * The first view of a node without stored count (created before the field).
   */
  public function testFirstViewWithoutStoredCount(): void {
    $node = $this->createNode([
      'type' => self::BUNDLE,
      'moderation_state' => 'published',
    ]);
    $database = \Drupal::database();
    foreach (['node__', 'node_revision__'] as $prefix) {
      $database->delete($prefix . self::FIELD)->condition('entity_id', $node->id())->execute();
    }
    $this->assertNull($this->reload($node)->get(self::FIELD)->value);

    $this->assertSame(1, $this->put($node->id())[1]['count']);
    $this->assertSame(2, $this->put($node->id())[1]['count']);
    $this->assertSame(2, (int) $this->reload($node)->get(self::FIELD)->value);
  }

  /**
   * Nodes the visitor may not view, or that do not exist, are not counted.
   */
  public function testNotCounted(): void {
    $node = $this->createNode([
      'type' => self::BUNDLE,
      'moderation_state' => 'draft',
    ]);
    $this->assertSame(404, $this->put($node->id())[0]);
    $this->assertEmpty($this->reload($node)->get(self::FIELD)->value);

    $this->assertSame(404, $this->put(999999999)[0]);
    $this->assertSame(404, $this->put('abc')[0]);
  }

  /**
   * Views are limited per node and per IP address.
   */
  public function testFlood(): void {
    $node = $this->createNode([
      'type' => self::BUNDLE,
      'moderation_state' => 'published',
    ]);
    for ($i = 0; $i < self::FLOOD_THRESHOLD; $i++) {
      $this->assertSame(200, $this->put($node->id())[0]);
    }
    $this->assertSame(429, $this->put($node->id())[0]);
    $this->assertSame(self::FLOOD_THRESHOLD, (int) $this->reload($node)->get(self::FIELD)->value);
  }

  /**
   * Sends the anonymous PUT of the front-end and returns status and body.
   */
  private function put($nid): array {
    $url = $this->baseUrl . '/node-views-count/update-count/' . $nid;
    $response = \Drupal::httpClient()->request('PUT', $url, [
      'http_errors' => FALSE,
      'verify' => FALSE,
    ]);
    $body = json_decode((string) $response->getBody(), TRUE);
    return [$response->getStatusCode(), $body];
  }

  /**
   * Loads the node again, without the static and persistent caches.
   */
  private function reload(NodeInterface $node): NodeInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $storage->resetCache([$node->id()]);
    return $storage->load($node->id());
  }

  /**
   * Number of revisions of the node.
   */
  private function revisionCount(NodeInterface $node): int {
    return count(\Drupal::entityTypeManager()->getStorage('node')->revisionIds($node));
  }

}
