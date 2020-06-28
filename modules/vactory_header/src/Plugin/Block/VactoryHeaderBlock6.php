<?php

namespace Drupal\vactory_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory Header Block 6" block.
 *
 * @Block(
 *   id = "vactory_header_block6",
 *   admin_label = @Translation("Vactory Header Block V6"),
 *   category = @Translation("Headers")
 * )
 */
class VactoryHeaderBlock6 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cachabelMetadata = new CacheableMetadata();
    $cachabelMetadata->setCacheContexts(['user.roles', 'url.path']);
    $user = \Drupal::currentUser();

    $alert = [
      'elements' => [
        [
          'url'   => '<front>',
          'label' => 'demande apprové',
        ],
        [
          'url'   => '<front>',
          'label' => 'demande non apprové',
        ],
        [
          'url'   => '<front>',
          'label' => 'demande en cours de traitement',
        ],
      ],
      'count'    => 3,
    ];

    $build = [
      "#cache" => ["max-age" => 0],
      "#theme" => "block_vactory_header6",
      "#user"  => [
        'id'   => $user->id(),
        'name' => $user->getAccountName(),
      ],
      "#alert" => $alert,
    ];

    $cachabelMetadata->addCacheableDependency($user);
    $cachabelMetadata->applyTo($build);

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')
      ->getEditable('vactory_header.settings');
    $config->set('variante_number', 6)->save();
  }

}
