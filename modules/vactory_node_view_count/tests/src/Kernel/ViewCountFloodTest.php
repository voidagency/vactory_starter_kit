<?php

namespace Drupal\Tests\vactory_node_view_count\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\vactory_node_view_count\Controller\VactoryNodeViewCountController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Limit of views counted per node and per IP address, set in settings.php.
 *
 * @group vactory_node_view_count
 */
class ViewCountFloodTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'vactory_node_view_count',
  ];

  /**
   * The node counted.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);
    $type = NodeType::create(['type' => 'page', 'name' => 'Page']);
    $type->setThirdPartySetting('vactory_node_view_count', 'enabling_count_node', TRUE);
    $type->save();
    FieldStorageConfig::create([
      'field_name' => 'field_node_count_view',
      'entity_type' => 'node',
      'type' => 'integer',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_node_count_view',
      'entity_type' => 'node',
      'bundle' => 'page',
      'default_value' => [['value' => 0]],
    ])->save();
    $this->setUpCurrentUser([], ['access content', 'bypass node access']);
    $this->node = Node::create(['type' => 'page', 'title' => 'Counted']);
    $this->node->save();
  }

  /**
   * Without the setting, views are not limited.
   */
  public function testNoLimitByDefault(): void {
    $this->assertSame(array_fill(0, 5, 200), $this->views(5, '203.0.113.1'));
  }

  /**
   * With the setting, views beyond the limit get 429 and are not counted.
   */
  public function testLimitFromSettings(): void {
    new Settings(['vactory_node_view_count_flood_threshold' => 3] + Settings::getAll());

    $this->assertSame([200, 200, 200, 429, 429], $this->views(5, '203.0.113.1'));
    // The limit is per IP address.
    $this->assertSame([200], $this->views(1, '203.0.113.2'));
    $count = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($this->node->id())->get('field_node_count_view')->value;
    $this->assertSame(4, (int) $count);
  }

  /**
   * The node cache tag is invalidated at most once per interval.
   */
  public function testInvalidationIsSpaced(): void {
    $before = $this->invalidations();
    for ($i = 0; $i < 5; $i++) {
      $this->views(1, '203.0.113.1');
      $this->invalidations();
    }
    $this->assertSame($before + 1, $this->invalidations(), 'One invalidation for 5 views.');

    // Once the interval is over, the next view invalidates again.
    \Drupal::keyValueExpirable('vactory_node_view_count.invalidated')->delete((string) $this->node->id());
    $this->views(1, '203.0.113.1');
    $this->assertSame($before + 2, $this->invalidations());

    // Interval set to 0: each view invalidates.
    new Settings(['vactory_node_view_count_invalidation_interval' => 0] + Settings::getAll());
    // Core counts one invalidation of a tag per request: each view is a
    // request of its own.
    for ($i = 0; $i < 3; $i++) {
      $this->views(1, '203.0.113.1');
      $this->invalidations();
    }
    $this->assertSame($before + 5, $this->invalidations());
  }

  /**
   * Number of invalidations of the node cache tag.
   */
  private function invalidations(): int {
    // Forgets the tags already invalidated during this request.
    \Drupal::service('cache_tags.invalidator.checksum')->reset();
    return (int) \Drupal::database()->select('cachetags', 'c')
      ->fields('c', ['invalidations'])
      ->condition('tag', 'node:' . $this->node->id())
      ->execute()
      ->fetchField();
  }

  /**
   * Sends views from the given IP address and returns the status codes.
   */
  private function views(int $number, string $ip): array {
    $controller = \Drupal::classResolver(VactoryNodeViewCountController::class);
    $codes = [];
    for ($i = 0; $i < $number; $i++) {
      $request = Request::create('/node-views-count/update-count/' . $this->node->id(), 'PUT', [], [], [], ['REMOTE_ADDR' => $ip]);
      $codes[] = $controller->incrementCounter($this->node->id(), $request)->getStatusCode();
    }
    return $codes;
  }

}
