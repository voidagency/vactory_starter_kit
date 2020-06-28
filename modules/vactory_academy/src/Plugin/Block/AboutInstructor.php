<?php

namespace Drupal\vactory_academy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Provide About academy instructor block.
 *
 * @Block(
 *   id = "vactory_academy_about_instructor",
 *   admin_label = @Translation("About instructor"),
 *   category = @Translation("Academy"),
 * )
 */
class AboutInstructor extends BlockBase {

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
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node instanceof NodeInterface && $node->bundle() == 'vactory_academy') {
      $instructor_id = $node->get('field_vactory_instructor')->getValue()[0]['target_id'];
      $instructor = User::load($instructor_id);
      if (isset($instructor)) {
        $content = get_academy_instructor_statistics($instructor);
        return [
          '#theme' => 'block_user_instructor_about',
          '#user' => $instructor,
          '#content' => $content,
        ];
      }
    }
  }

  /**
   * Disable block cache.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
