<?php

namespace Drupal\vactory_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory Header AMP" block.
 *
 * @Block(
 *   id = "block_vactory_header_amp",
 *   admin_label = @Translation("Vactory Header AMP"),
 *   category = @Translation("Headers"),
 * )
 */
class VactoryHeaderBlockAMP extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      "#cache" => ["max-age" => 0],
      "#theme" => "block_vactory_header_amp",
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')
      ->getEditable('vactory_header.settings');
    $config->set('variante_number', 12)->save();
  }

}
