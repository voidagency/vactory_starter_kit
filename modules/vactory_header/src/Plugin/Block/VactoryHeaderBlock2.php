<?php

namespace Drupal\vactory_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory Header Block 2" block.
 *
 * @Block(
 *   id = "vactory_header_block2",
 *   admin_label = @Translation("Vactory Header Block V2"),
 *   category = @Translation("Headers")
 * )
 */
class VactoryHeaderBlock2 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      "#cache" => ["max-age" => 0],
      "#theme" => "block_vactory_header2",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')
      ->getEditable('vactory_header.settings');
    $config->set('variante_number', 2)->save();
  }

}
