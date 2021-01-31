<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Class AgencySelectController.
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AgencySelectController extends ControllerBase {

  /**
   * Content callback function.
   */
  public function content() {
    $site_name = \Drupal::config('system.site')->get('name');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user_has_access = \Drupal::service('vactory_appointment.appointments.manage')->isCurrentUserCanSubmitAppointment();
    if ($user_has_access) {
      $agency_entities = \Drupal::entityTypeManager()->getStorage('locator_entity')
        ->loadByProperties([
          'type' => 'vactory_locator',
          'status' => 1,
        ]);
      $content = [];
      if (!empty($agency_entities)) {
        foreach ($agency_entities as $key => $agency) {
          $is_appointment_enabled = $agency->get('field_is_appointment_enabled')->value;
          if ($is_appointment_enabled) {
            $agency = \Drupal::service('entity.repository')->getTranslationFromContext($agency, $langcode);
            $content[$key]['title'] = $agency->get('name')->value;
            $content[$key]['agency_id'] = $agency->id();
            $agency_path = $agency->get('field_agency_path')->value;
            $content[$key]['agency_path'] = $agency_path;
            $mid = $agency->get('field_locator_image')->target_id;
            if ($mid) {
              $media = Media::load($mid);
              $fid = $media->get('field_media_image')->target_id;
              $file = $fid ? File::load($fid) : NULL;
              if ($file) {
                $content[$key]['image_uri'] = $file->get('uri')->value;
              }
            }
          }
        }
      }
      return [
        '#theme' => 'appointment_agencies_listing',
        '#content' => $content,
      ];
    }
    $destination = '/' . $langcode . '/prendre-un-rendez-vous';
    $url_login = Url::fromRoute('vactory_espace_prive.login', ['destination' => $destination])->toString();
    $url_register = Url::fromRoute('vactory_espace_prive.register', ['destination' => $destination])->toString();
    return [
      '#theme' => 'appointment_require_authentication_message',
      '#title' => t('Pour prendre un rendez-vous, veuillez vous connecter à votre compte @site_name.', ['@site_name' => $site_name]),
      '#url_login' => $url_login,
      '#url_register' => $url_register,
    ];
  }

}
