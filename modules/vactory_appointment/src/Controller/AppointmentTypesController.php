<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppointmentTypesController
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AppointmentTypesController extends ControllerBase {

  public function content($agency) {
    $appointment_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('dam_motifs');
    $content = [];
    $properties = [
      'vid' => 'dam_agencies',
      'field_path_agency' => $agency,
    ];
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    if (!empty($terms)) {
      foreach ($appointment_types as $key => $type) {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $tid = $type->tid;
        $term = Term::load($tid);
        $content[$key]['title'] = $term->get('name')->value;
        $content[$key]['appointment_type_id'] = $tid;
        $appointment_type_path = $term->get('field_path_motif_name')->value;
        $path = '/' . $langcode . '/borne/' . $agency . '/' . $appointment_type_path;
        $content[$key]['appointment_type_path'] = $path;
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
    else {
      redirect_to_notfound();
    }

  }
}
