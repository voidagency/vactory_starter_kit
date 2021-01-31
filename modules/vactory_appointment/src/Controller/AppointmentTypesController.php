<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppointmentTypesController.
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AppointmentTypesController extends ControllerBase {

  /**
   * Content callback function.
   */
  public function content($agency) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $site_name = \Drupal::config('system.site')->get('name');
    $appointment_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'vactory_appointment_motifs']);
    $content = [];
    $agency_properties = [
      'type' => 'vactory_locator',
      'field_agency_path' => $agency,
      'status' => 1,
    ];
    $agency_entities = \Drupal::entityTypeManager()
      ->getStorage('locator_entity')
      ->loadByProperties($agency_properties);
    if (!empty($agency_entities)) {
      $user_has_access = \Drupal::service('vactory_appointment.appointments.manage')->isCurrentUserCanSubmitAppointment();
      if ($user_has_access) {
        $agency_entity = array_values($agency_entities)[0];
        $is_appointment_enabled = $agency_entity->get('field_is_appointment_enabled')->value;
        if (!$is_appointment_enabled) {
          redirect_to_notfound();
        }
        foreach ($appointment_types as $key => $term) {
          $term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
          $content['appointment_types'][$key]['title'] = $term->getName();
          $content['appointment_types'][$key]['appointment_type_id'] = $term->id();
          $appointment_type_path = $term->get('field_path_motif_name')->value;
          $content['appointment_types'][$key]['appointment_type_path'] = $appointment_type_path;
          $content['appointment_types'][$key]['agency'] = $agency;
          $mid = $term->get('field_motifs_image')->target_id;
          $media = Media::load($mid);
          $fid = $media->field_media_image->target_id;
          $file = File::load($fid);
          if ($file) {
            $content['appointment_types'][$key]['image_uri'] = $file->get('uri')->value;
          }
        }
        return [
          '#theme' => 'appointment_types_listing',
          '#content' => $content,
        ];
      }
      $url = Url::fromRoute('vactory_appointment.site_agency_select')->toString();
      $response = new RedirectResponse($url);
      return $response->send();
    }
    else {
      redirect_to_notfound();
      return NULL;
    }
  }

}
