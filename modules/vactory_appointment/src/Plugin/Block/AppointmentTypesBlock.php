<?php

namespace Drupal\vactory_appointment\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Provide Appointment types Listing block.
 *
 * @Block(
 *   id = "vactory_appointment_types",
 *   admin_label = @Translation("Appointment types"),
 *   category = @Translation("Appointment"),
 * )
 */
class AppointmentTypesBlock extends BlockBase {

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
    $appointment_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('dam_motifs');
    $content = [];
    foreach ($appointment_types as $key => $type) {
      $tid = $type->tid;
      $term = Term::load($tid);
      $content[$key]['title'] = $term->get('name')->value;
      $content[$key]['appointment_type_id'] = $tid;
      $content[$key]['appointment_type_path'] = $term->get('field_path_motif_name')->value;
      $fid = $term->get('field_motifs_image')->target_id;
      $file = File::load($fid);
      if ($file) {
        $content[$key]['image_uri'] = $file->get('uri')->value;
      }
    }

    return [
      '#theme' => 'appointment_types_listing',
      '#content' => $content,
    ];
  }
}
