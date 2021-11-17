<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 4" block.
 *
 * @Block(
 *   id = "vactory_footer_block4",
 *   admin_label = @Translation("Vactory Footer Block V4"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock4 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#theme"   => "block_vactory_footer4",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
