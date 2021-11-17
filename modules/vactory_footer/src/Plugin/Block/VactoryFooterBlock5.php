<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 5" block.
 *
 * @Block(
 *   id = "vactory_footer_block5",
 *   admin_label = @Translation("Vactory Footer Block V5"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock5 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      "#theme" => "block_vactory_footer5",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
