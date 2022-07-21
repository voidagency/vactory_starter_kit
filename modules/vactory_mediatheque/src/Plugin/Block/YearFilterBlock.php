<?php

namespace Drupal\vactory_mediatheque\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Drupalup Block' Block.
 *
 * @Block(
 *   id = "year_block_filter",
 *   admin_label = @Translation("Médiathèque Year Block"),
 *   category = @Translation("Vactory"),
 * )
 */
class YearFilterBlock extends BlockBase {

  /**
   * Function build.
   */
  public function build() {
    $current_active_year = isset(\Drupal::request()->query) && !empty(\Drupal::request()->query->get('field_medium_year_target_id')) ? \Drupal::request()->query->get('field_medium_year_target_id') : '';
    $properties = [
      'vid' => 'medium_year',
    ];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $terms = array_values($terms);
    $years = [];
    if (isset($terms) && !empty($terms)) {
      foreach ($terms as $term) {
        $years[$term->id()] = $term->getName();
      }
    }
    return [
      '#theme' => 'block_year_filter',
      '#years' => $years,
      '#current_active_year' => $current_active_year,
    ];
  }

}
