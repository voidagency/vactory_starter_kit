<?php

namespace Drupal\vactory_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory Header Block 1" block.
 *
 * @Block(
 *   id = "vactory_header_block1",
 *   admin_label = @Translation("Vactory Header Block V1"),
 *   category = @Translation("Headers"),
 * )
 */
class VactoryHeaderBlock1 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      "#cache" => array("max-age" => 0),
      "#theme" => "block_vactory_header1",
    );

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('vactory_header.settings');
    $config->set('variante_number', 1)->save();
  }

}
