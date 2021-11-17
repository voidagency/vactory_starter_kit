<?php

namespace Drupal\vactory_content_localizer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Entity\View;

/**
 * Provides a 'Distance Filter' Block.
 *
 * @Block(
 *   id = "vactory_content_distance_filter",
 *   admin_label = @Translation("Vactory Content Distance Filter block"),
 *   category = @Translation("Vactory"),
 * )
 */
class DistanceFilterBlock extends BlockBase {

  /**
   * Distance filter block build function.
   */
  public function build() {
    $view_id = \Drupal::routeMatch()->getParameter('view_id');
    $distances = [];
    if (isset($view_id)) {
      $display_id = \Drupal::routeMatch()->getParameter('display_id');
      if ($display_id) {
        $view = View::load($view_id);

        // Get distances from current display.
        $current_display = $view->getDisplay($display_id);
        $distances = array_map('intval', explode(' ', $current_display['display_options']['filters']['field_vactory_position_proximity']['expose']['description']));
      }
    }

    // Associate the distances with its units.
    $distances_with_units = [];
    if (!empty($distances)) {
      foreach ($distances as $distance) {
        $item = [];
        if ($distance >= 1000) {
          $item['distance'] = round($distance / 1000, 2);
          $item['unit'] = 'km';
        }
        else {
          $item['distance'] = round($distance, 0);
          $item['unit'] = 'm';
        }
        array_push($distances_with_units, $item);
      }
    }

    // Render distances block.
    return [
      '#theme' => 'vactory_content_distance_filter_block',
      '#content' => [
        'distances' => $distances_with_units,
      ],
    ];
  }

}
