<?php

namespace Drupal\vactory_anchor\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;
use Drupal\vactory_anchor\AnchorMenuTrait;

/**
 * Provides a 'Anchor' Block.
 *
 * @Block(
 *   id = "anchor_drop_block",
 *   admin_label = @Translation("Menu d'ancre Dropdown"),
 *   category = @Translation("Vactory"),
 * )
 */
class AnchorDropdownBlock extends BlockBase {

  use AnchorMenuTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return NULL;
    }

    $menu_links = $this->getNodeParagraphs($node);

    return [
      "#theme"      => "block_anchor_dropdown",
      '#menu_links' => $menu_links,
    ];
  }

}
