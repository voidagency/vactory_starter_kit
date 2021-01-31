<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\vactory_appointment\Entity\AppointmentEntity;

/**
 * Class AppointmentDeleteController.
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AppointmentDeleteController extends ControllerBase {

  /**
   * Delete appointment page content.
   */
  public function deleteAppointmentSubmit($appointment_id) {
    $user_has_access = \Drupal::service('vactory_appointment.appointments.manage')->isCurrentUserCanSubmitAppointment();
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $site_name = \Drupal::config('system.site')->get('name');
    if ($user_has_access) {
      $content = [];
      $content['page_type'] = 'delete_form';
      $content['appointment_id'] = $appointment_id;
      $content['agence'] = \Drupal::routeMatch()->getParameter('agency');
      return [
        '#theme' => 'appointment_deletion_page',
        '#content' => $content,
      ];
    }
    $destination = '/' . $langcode . '/modifier-un-rendez-vous';
    $url_login = Url::fromRoute('vactory_espace_prive.login', ['destination' => $destination])->toString();
    $url_register = Url::fromRoute('vactory_espace_prive.register', ['destination' => $destination])->toString();
    return [
      '#theme' => 'appointment_require_authentication_message',
      '#title' => t('Pour modifier un rendez-vous, veuillez vous connecter à votre compte @site_name.', ['@site_name' => $site_name]),
      '#url_login' => $url_login,
      '#url_register' => $url_register,
    ];
  }

  /**
   * Delete appointment confirmation page content.
   */
  public function deleteAppointmentConfirmation($appointment_id) {
    $user_has_access = \Drupal::service('vactory_appointment.appointments.manage')->isCurrentUserCanSubmitAppointment();
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $site_name = \Drupal::config('system.site')->get('name');
    if ($user_has_access) {
      $content = [];
      $content['page_type'] = 'delete_confirmation';
      $aid = decrypt($appointment_id);
      $appointment = AppointmentEntity::load($aid);
      if ($appointment) {
        try {
          $appointment->delete();
        }
        catch (EntityStorageException $e) {
          $content['error_message'] = t('Une erreur est survenue lors de la suppression de votre rendez-vous, Veuillez réessayer plus tard.');
          \Drupal::logger('vactory_appointment')->warning($e->getMessage());
        }
      }
      return [
        '#theme' => 'appointment_deletion_page',
        '#content' => $content,
      ];
    }
    $destination = '/' . $langcode . '/modifier-un-rendez-vous';
    $url_login = Url::fromRoute('vactory_espace_prive.login', ['destination' => $destination])->toString();
    $url_register = Url::fromRoute('vactory_espace_prive.register', ['destination' => $destination])->toString();
    return [
      '#theme' => 'appointment_require_authentication_message',
      '#title' => t('Pour modifier un rendez-vous, veuillez vous connecter à votre compte @site_name.', ['@site_name' => $site_name]),
      '#url_login' => $url_login,
      '#url_register' => $url_register,
    ];
  }

}
