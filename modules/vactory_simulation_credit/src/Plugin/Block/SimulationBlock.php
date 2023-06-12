<?php

namespace Drupal\vactory_simulation_credit\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provide a block of Simulation Credit.
 *
 * @Block(
 *  id = "vactory_simulation_credit_block",
 *  admin_label = @Translation("Vactory Simulation Block"),
 *  category = "Simulation Credit"
 * )
 */
class SimulationBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build() {
    return [
      '#theme' => 'simulation_block',
      '#cache' => [
        // Set the caching policy to match the default block caching policy.
        'max-age' => 0,
        'contexts' => ['url'],
        'tags' => ['rendered'],
      ],
    ];
  }

}
