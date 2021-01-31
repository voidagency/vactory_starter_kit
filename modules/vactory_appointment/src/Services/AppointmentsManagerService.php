<?php

namespace Drupal\vactory_appointment\Services;

use DateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Drupal\vactory_appointment\Entity\AppointmentEntity;

/**
 * Class AppointmentsManagerService.
 */
class AppointmentsManagerService {

  use StringTranslationTrait;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new AppointmentsManagerService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Check if an adviser is available.
   */
  public function isAdviserAvailable(UserInterface $adviser, $date_timestamp, $is_edit_existing, $appointment_id = NULL) {
    $adviser_appointments = $this->getAdviserAppointments($adviser);
    $adviser_holidays = $this->getAdviserHolidays($adviser);
    foreach ($adviser_appointments as $adviser_appointment) {
      $appointment_timestamp = (new DateTime($adviser_appointment['start']))->getTimestamp();
      if ($appointment_timestamp === $date_timestamp && !$is_edit_existing) {
        return FALSE;
      }
      if ($appointment_timestamp === $date_timestamp && $adviser_appointment['id'] !== $appointment_id) {
        return FALSE;
      }
    }
    foreach ($adviser_holidays as $adviser_holiday) {
      $holiday_timestamp = (new DateTime($adviser_holiday['start']))->getTimestamp();
      if ($holiday_timestamp === $date_timestamp) {
        return FALSE;
      }
    }
    $choosedDate = new DateTime();
    $choosedDate->setTimestamp($date_timestamp);
    $day = $choosedDate->format('N');
    $hour = $choosedDate->format('H');
    if ($day == 7 || $hour > 17 || $hour < 8) {
      // Sunday case.
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if an appointment is associated to and adviser.
   */
  public function isAdviserHasAppointment(UserInterface $adviser, AppointmentEntity $appointment) {
    $adviser_appointments = $this->getAdviserAppointments($adviser);
    foreach ($adviser_appointments as $adviser_appointment) {
      if ($adviser_appointment['id'] == $appointment->id()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Update adviser appointments.
   */
  public function updateAdviserAppointments(UserInterface $adviser, AppointmentEntity $appointment, $is_edit_existing = FALSE) {
    $date_string = $appointment->getAppointmentDate();
    $date = new DateTime($date_string);
    if ($this->isAdviserAvailable($adviser, $date->getTimestamp(), $is_edit_existing, $appointment->id())) {
      $adviser_appointments = $this->getAdviserAppointments($adviser);
      $aid = (string) $appointment->id();
      if ($this->isAdviserHasAppointment($adviser, $appointment)) {
        $appointment_index = $this->getAppointmentIndex($adviser_appointments, $aid);
        if (isset($appointment_index)) {
          $adviser_appointments[$appointment_index] = [
            'id' => $aid,
            'start' => $date_string,
            'title' => t('Indisponible'),
            'color' => '#2196f3',
            'textColor' => '#ffffff',
            'editable' => FALSE,
            'overlap' => FALSE,
          ];
        }
      }
      else {
        $adviser_appointments[] = [
          'id' => $aid,
          'start' => $date_string,
          'title' => t('Indisponible'),
          'color' => '#2196f3',
          'textColor' => '#ffffff',
          'editable' => FALSE,
          'overlap' => FALSE,
        ];
      }
      $this->setAdviserAppointments($adviser, $adviser_appointments);
    }
  }

  /**
   * Unlink an appointment from a given adviser.
   */
  public function removeAdviserAppointmentIfExist(UserInterface $adviser, AppointmentEntity $appointment) {
    $aid = $appointment->id();
    $adviser_appointments = $this->getAdviserAppointments($adviser);
    if ($this->isAdviserHasAppointment($adviser, $appointment)) {
      $appointment_index = $this->getAppointmentIndex($adviser_appointments, $aid);
      if (isset($appointment_index)) {
        unset($adviser_appointments[$appointment_index]);
      }
      $this->setAdviserAppointments($adviser, $adviser_appointments);
    }
  }

  /**
   * Get all appointments of the given adviser.
   */
  public function getAdviserAppointments(UserInterface $adviser) {
    return json_decode($adviser->get('field_adviser_appointments')->value, TRUE);
  }

  /**
   * Get holidays of the given adviser.
   */
  public function getAdviserHolidays(UserInterface $adviser) {
    return json_decode($adviser->get('field_adviser_holiday')->value, TRUE);
  }

  /**
   * Set appointments to the given adviser.
   */
  public function setAdviserAppointments(UserInterface $adviser, array $adviser_appointments) {
    $adviser->set('field_adviser_appointments', json_encode($adviser_appointments));
    $adviser->save();
  }

  /**
   * Get Appointment index.
   */
  public function getAppointmentIndex($adviser_appointments, $aid) {
    foreach ($adviser_appointments as $key => $appointment) {
      if ($appointment['id'] == $aid) {
        return $key;
      }
    }
    return NULL;
  }

  /**
   * Get adviser appointment by ID.
   */
  public function getAdviserAppointment($adviser, $appointment_id) {
    global $_appointment_id_;
    $_appointment_id_ = $appointment_id;
    $adviser_appointments = $adviser->get('field_adviser_appointments')->value;
    $adviser_appointments = json_decode($adviser_appointments, TRUE);
    $adviser_appointment = array_filter($adviser_appointments, function ($appointment) {
      global $_appointment_id_;
      return $appointment['id'] == $_appointment_id_;
    });
    unset($_appointment_id_);
    return !empty($adviser_appointment) ? array_values($adviser_appointment)[0] : NULL;
  }

  /**
   * Remove given appointment from given adviser.
   */
  public function removeAdviserAppointment($adviser, $appointment_id) {
    global $_appointment_id_;
    $_appointment_id_ = $appointment_id;
    $adviser_appointments = $adviser->get('field_adviser_appointments')->value;
    $adviser_appointments = json_decode($adviser_appointments, TRUE);
    $removed_appointment = array_filter($adviser_appointments, function ($appointment) {
      global $_appointment_id_;
      return $appointment['id'] == $_appointment_id_;
    });
    $adviser_appointments = array_filter($adviser_appointments, function ($appointment) {
      global $_appointment_id_;
      return $appointment['id'] !== $_appointment_id_;
    });
    $adviser->set('field_adviser_appointments', json_encode(array_values($adviser_appointments)));
    $adviser->save();
    unset($_appointment_id_);
    return !empty($removed_appointment) ? array_values($removed_appointment)[0] : NULL;
  }

  /**
   * Add given appointment to given adviser.
   */
  public function addAdviserAppointment($adviser, $appointment) {
    $adviser_appointments = $adviser->get('field_adviser_appointments')->value;
    $adviser_appointments = json_decode($adviser_appointments, TRUE);
    $adviser_appointments[] = $appointment;
    $adviser->set('field_adviser_appointments', json_encode($adviser_appointments));
    $adviser->save();
  }

  public function isCurrentUserCanSubmitAppointment() {
    $config = \Drupal::config('vactory_appointment.settings');
    return $config->get('is_authentication_required') ? !\Drupal::currentUser()->isAnonymous() : TRUE;
  }

}
