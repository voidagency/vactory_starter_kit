<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
      "#cache" => ["max-age" => 0],
      "#theme" => "block_vactory_footer_amp",
    ];

  }

}
