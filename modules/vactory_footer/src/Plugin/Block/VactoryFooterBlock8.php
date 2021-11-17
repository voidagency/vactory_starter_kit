<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 8" block.
 *
 * @Block(
 *   id = "vactory_footer_block8",
 *   admin_label = @Translation("Vactory Footer Block V8"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock8 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      "#theme"   => "block_vactory_footer8",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
