<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a "Vactory Footer Block AMP" block.
 *
 * @Block(
 *   id = "block_vactory_footer_amp",
 *   admin_label = @Translation("Vactory Footer Block AMP"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlockAMP extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#theme" => "block_vactory_footer_amp",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
