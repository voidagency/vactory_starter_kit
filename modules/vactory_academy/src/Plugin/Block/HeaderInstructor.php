<?php

namespace Drupal\vactory_academy\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provide a secondary header block for authenticated users.
 *
 * @Block (
 *   id = "authenticated_user_header",
 *   admin_label = @Translation("Authenticated user header"),
 *   category = @Translation("Academy"),
 * )
 */
class HeaderInstructor extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    if (!in_array('anonymous', $current_user->getRoles())) {
      return [
        '#theme' => 'block_authenticated_user_header',
        '#user' => $current_user,
      ];
    }
    else {
      return [];
    }
  }

}
