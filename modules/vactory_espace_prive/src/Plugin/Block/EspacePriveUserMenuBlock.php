<?php

namespace Drupal\vactory_espace_prive\plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Class EspacePriveBlock.
 *
 * @package Drupal\vactory_espace_prive\plugin\Block
 * @Block(
 *   id = "vactory_espace_prive_user_menu",
 *   admin_label = @Translation("Vactory Espace Privé User Menu Block"),
 *   category = @Translation("Vactory")
 * )
 */
class EspacePriveUserMenuBlock extends BlockBase {

  /**
   * Espace prive user menu block build().
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $display_name = '';
    $user_id = NULL;
    if (!$current_user->isAnonymous()) {
      $display_name = $current_user->getDisplayName();
      $user_id = $current_user->id();
    }
    $user = [
      'is_authenticated' => !$current_user->isAnonymous(),
      'display_name' => $display_name,
      'user_id' => $user_id,
    ];
    return [
      '#theme' => 'block_espace_prive_user_menu',
      "#content" => [
        'user' => $user,
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    return ['menu_link_content_list:vactory-espace-prive-menu'];
  }

}
