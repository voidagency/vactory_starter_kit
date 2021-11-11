<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block 1" block.
 *
 * @Block(
 *   id = "vactory_footer_block1",
 *   admin_label = @Translation("Vactory Footer Block V1"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock1 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#theme" => "block_vactory_footer1",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
