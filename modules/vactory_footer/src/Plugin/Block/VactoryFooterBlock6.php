<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 6" block.
 *
 * @Block(
 *   id = "vactory_footer_block6",
 *   admin_label = @Translation("Vactory Footer Block V6"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock6 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#theme"   => "block_vactory_footer6",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
