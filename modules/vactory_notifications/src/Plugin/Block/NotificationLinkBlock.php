<?php

namespace Drupal\vactory_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\views\Entity\View;

/**
 * Provides a notifications listing page link.
 *
 * @Block(
 *   id = "vactory_notifications_link",
 *   admin_label = @Translation("Vactory Notifications Link"),
 *   category = @Translation("Vactory"),
 * )
 */
class NotificationLinkBlock extends BlockBase {

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
    if ($current_user->hasPermission('view notifications')) {
      $current_user = \Drupal::currentUser();
      $uid = '%"' . $current_user->id() . '"%';
      $query = \Drupal::entityQuery('notifications_entity')
        ->condition('notification_concerned_users', $uid, 'LIKE')
        ->condition('notification_viewers', $uid, 'NOT LIKE')
        ->condition('status', 1)
        ->accessCheck(TRUE);
      $results = $query->execute();
      $new_notifications_counter = is_array($results) ? count($results) : 0;
      $notification_view = View::load('notifications');
      $path = '/' . $notification_view->getDisplay('listing')['display_options']['path'];
      $url = Url::fromUserInput($path)->toString();

      // If user has more than 99 notification then just print '+99'.
      $new_notifications_counter = $new_notifications_counter > 99 ? '+99' : $new_notifications_counter;
      return [
        '#theme' => 'vactory_notifications_link',
        '#url' => $url,
        '#nb_new_notifications' => $new_notifications_counter,
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    return ['notifications_entity:view'];
  }

}
