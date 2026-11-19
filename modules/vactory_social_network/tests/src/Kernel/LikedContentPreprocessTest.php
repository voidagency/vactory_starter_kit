<?php

namespace Drupal\Tests\vactory_social_network\Kernel;

use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests vactory_social_network_preprocess_flag__liked_content().
 *
 * @group vactory_social_network
 */
class LikedContentPreprocessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'flag',
    'vactory_social_network',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('flagging');
    $this->installSchema('flag', ['flag_counts']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['flag']);
    NodeType::create(['type' => 'vactory_post', 'name' => 'Post'])->save();
    Flag::create([
      'id' => 'liked_content',
      'label' => 'Like',
      'entity_type' => 'node',
      'bundles' => ['vactory_post'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
      'global' => FALSE,
    ])->save();
    $this->container->get('module_handler')->loadInclude('vactory_social_network', 'module');
  }

  /**
   * The preprocess counts the likes of the node.
   */
  public function testLikesCount(): void {
    $node = Node::create(['type' => 'vactory_post', 'title' => 'Post']);
    $node->save();
    foreach ([$this->createUser(), $this->createUser()] as $account) {
      Flagging::create([
        'flag_id' => 'liked_content',
        'entity_type' => 'node',
        'entity_id' => $node->id(),
        'uid' => $account->id(),
      ])->save();
    }

    $variables = ['flaggable' => $node];
    vactory_social_network_preprocess_flag__liked_content($variables);
    $this->assertEquals(2, $variables['likes_count']);
  }

}
