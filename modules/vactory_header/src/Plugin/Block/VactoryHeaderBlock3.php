<?php

namespace Drupal\vactory_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory Header Block 3" block.
 *
 * @Block(
 *   id = "vactory_header_block3",
 *   admin_label = @Translation("Vactory Header Block V3"),
 *   category = @Translation("Headers")
 * )
 */
class VactoryHeaderBlock3 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      "#theme" => "block_vactory_header3",
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('vactory_header.settings');
    $config->set('variante_number', 3)->save();

    $values = $form_state->getValues();
    $this->configuration['use_lang_code'] = $values['use_lang_code'];
  }

  /**
   * {@inheritDoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['use_lang_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use language code as language switcher.'),
      '#default_value' => isset($this->configuration['use_lang_code']) ? $this->configuration['use_lang_code'] : \Drupal::config('vactory_header.settings')->get('use_lang_code'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
