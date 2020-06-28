<?php

namespace Drupal\vactory_governance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides a "Governance Member" block.
 *
 * @Block(
 *   id = "vactory_governance_member",
 *   admin_label = @Translation("Membre du gouvernement"),
 *   category = @Translation("Governance")
 * )
 */
class GovernanceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $cachabelmetadata = new CacheableMetadata();
    $cachabelmetadata->addCacheContexts(['url.path']);

    $content = [];
    $vid = 'vactory_governance_role';
    $terms = \Drupal::service('entity.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($vid);

    $content['terms'] = $terms;

    $build = [
      "#theme" => "governance_member_block",
      '#content' => $content,
    ];

    foreach ($terms as $term) {
      $cachabelmetadata->addCacheableDependency($term);
    }

    $cachabelmetadata->applyTo($build);

    return $build;

  }

}
