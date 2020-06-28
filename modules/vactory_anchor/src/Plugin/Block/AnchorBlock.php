<?php

namespace Drupal\vactory_anchor\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\vactory_anchor\AnchorMenuTrait;

/**
 * Provides a 'Anchor' Block.
 *
 * @Block(
 *   id = "anchor_block",
 *   admin_label = @Translation("Menu d'ancre"),
 *   category = @Translation("Vactory"),
 * )
 */
class AnchorBlock extends BlockBase {

  use AnchorMenuTrait;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['block_field'] = [
      '#type'          => 'block_field',
      '#title'         => $this->t('Block field'),
      '#title_display' => FALSE,
      '#default_value' => isset($config['block_field']) ? $config['block_field'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['block_field'] = $values['block_field'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return NULL;
    }

    $config = $this->getConfiguration();
    $custom_block = isset($config['block_field']['plugin_id']) ? $config['block_field']['plugin_id'] : NULL;

    $menu_links = $this->getNodeParagraphs($node);

    return [
      "#theme"        => "block_anchor",
      '#menu_links'   => $menu_links,
      '#custom_block' => $custom_block,
    ];
  }

}
