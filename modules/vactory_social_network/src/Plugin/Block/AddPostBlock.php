<?php

namespace Drupal\vactory_social_network\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Add Post Block' Block.
 *
 * @Block(
 *   id = "vactory_social_network_add_block",
 *   admin_label = @Translation("Social Network - Add Post"),
 *   category = @Translation("vactory"),
 * )
 */
class AddPostBlock extends BlockBase {

  /**
   * Block base build function.
   */
  public function build() {
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('vactory_social_network_add_post');
    $form = $webform->getSubmissionForm();
    return [
      '#theme' => 'vactory_social_add_post',
      '#content' => [
        'form' => $form,
      ],
    ];
  }

}
