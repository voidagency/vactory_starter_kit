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
  public function isAdviserAvailable(UserInterface $adviser, DateTime $day, $hour) {
    $day = $day->format('Y-m-d');
    $adviser_appointments = $this->getAdviserAppointments($adviser);
    foreach ($adviser_appointments as $adviser_appointment) {
      if ($adviser_appointment['day'] === $day && $adviser_appointment['hour'] === $hour) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if an appointment is associated to and adviser.
   */
  public function isAdviserHasAppointment(UserInterface $adviser, AppointmentEntity $appointment) {
    return $adviser->id() === $appointment->getAdviser()->id();
  }

  /**
   * Update adviser appointments.
   */
  public function updateAdviserAppointments(UserInterface $adviser, AppointmentEntity $appointment) {
    $hour = $appointment->getAppointmentHour();
    $day = $appointment->getAppointmentDay();
    if ($this->isAdviserAvailable($adviser, $day, $hour)) {
      $adviser_appointments = $this->getAdviserAppointments($adviser);
      $aid = (string) $appointment->id();
      if ($this->isAdviserHasAppointment($adviser, $appointment)) {
        $adviser_appointments[$aid]['day'] = $day->format('Y-m-d');
        $adviser_appointments[$aid]['hour'] = $hour;
      }
      else {
        $new_appointment = [
          $aid => [
            'day'  => $day->format('Y-m-d'),
            'hour' => $hour,
          ],
        ];
        $adviser_appointments += $new_appointment;
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
      dump($adviser_appointments);
      unset($adviser_appointments[$aid]);
      dump($adviser_appointments);
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
   * Set appointments to the given adviser.
   */
  public function setAdviserAppointments(UserInterface $adviser, array $adviser_appointments) {
    $adviser->set('field_adviser_appointments', json_encode($adviser_appointments));
    $adviser->save();
  }

}
