<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 2" block.
 *
 * @Block(
 *   id = "vactory_footer_block2",
 *   admin_label = @Translation("Vactory Footer Block V2"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock2 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#theme" => "block_vactory_footer2",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
