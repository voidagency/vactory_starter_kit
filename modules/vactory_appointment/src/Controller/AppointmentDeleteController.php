<?php

namespace Drupal\vactory_appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\vactory_appointment\Entity\AppointmentEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppointmentDeleteController
 *
 * @package Drupal\vactory_appointment\Controller
 */
class AppointmentDeleteController extends ControllerBase {

  public function deleteAppointmentSubmit($appointment_id) {
    $content = [];
    $content['page_type'] = 'delete_form';
    $content['appointment_id'] = $appointment_id;
    return [
      '#theme' => 'appointment_deletion_page',
      '#content' => $content,
    ];
  }
  public function deleteAppointmentConfirmation($appointment_id) {
    $content = [];
    $content['page_type'] = 'delete_confirmation';
    $aid = decrypt($appointment_id);
    $appointment = AppointmentEntity::load($aid);
    if ($appointment) {
      try {
        $appointment->delete();
      } catch (EntityStorageException $e) {
        $content['error_message'] = t('Une erreur est survenue lors de la suppression de votre rendez-vous, Veuillez réessayer plus tard.');
        \Drupal::logger('vactory_appointment')->warning($e->getMessage());
      }
    }
    return [
      '#theme' => 'appointment_deletion_page',
      '#content' => $content,
    ];
  }
}
