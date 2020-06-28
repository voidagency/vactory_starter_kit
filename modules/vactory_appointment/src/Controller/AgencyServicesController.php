<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppointmentTypesController
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AgencyServicesController extends ControllerBase {

  public function content($agency_path) {
    $content = [];
    $properties = [
      'vid' => 'dam_agencies',
      'field_path_agency' => $agency_path,
    ];
    $agencies = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    if (!empty($agencies)) {
      $agency_name = $agencies[array_keys($agencies)[0]]->getName();
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $appointment_path = '/' . $langcode . '/borne/' . $agency_path . '/prendre-rendez-vous';
      $appointment_edit_path = '/' . $langcode . '/borne/modifier-rendez-vous';
      $content['agency_name'] = $agency_name;
      $content['appointment_path'] = $appointment_path;
      $content['appointment_edit_path'] = $appointment_edit_path;
      return [
        '#theme' => 'agency_services_listing',
        '#content' => $content,
      ];
    }
    else {
      redirect_to_notfound();
    }

  }
}
