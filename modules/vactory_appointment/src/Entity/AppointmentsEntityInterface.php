<?php

namespace Drupal\vactory_appointment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining an Appointment entity.
 *
 * @ingroup vactory_appointment
 */
interface AppointmentsEntityInterface extends  ContentEntityInterface, EntityOwnerInterface {

  /**
   * Check if current appointment is available.
   *
   * @return boolean
   */
  public function isAvailable();

  /**
   * Get current appointment title.
   *
   * @return String
   */
  public function getTitle();

  /**
   * Set current appointment title.
   *
   * @param $title
   *
   * @return \Drupal\vactory_appointment\Entity\AppointmentEntity
   */
  public function setTitle($title);

  /**
   * Returns the appointment adviser.
   *
   * @return \Drupal\user\UserInterface
   *   The appointment adviser.
   */
  public function getAdviser();

  /**
   * Sets the appointment adviser.
   *
   * @param \Drupal\user\UserInterface $account
   *   The appointment adviser.
   *
   * @return $this
   */
  public function setAdviser(UserInterface $account);

  /**
   * Returns the appointment agency.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The appointment agency.
   */
  public function getAgency();

  /**
   * Sets the appointment agency.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The appointment agency.
   *
   * @return $this
   */
  public function setAgency(Term $agency);

  /**
   * Returns the appointment type.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The appointment type.
   */
  public function getAppointmentType();

  /**
   * Sets the appointment type.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The appointment type.
   *
   * @return $this
   */
  public function setAppointmentType(Term $type);

  /**
   * Returns the appointment first name.
   *
   * @return string
   *   The appointment first name.
   */
  public function getAppointmentFirstName();

  /**
   * Sets the appointment first name.
   *
   * @param string $first_name
   *   The appointment first name.
   *
   * @return $this
   */
  public function setAppointmentFirstName($first_name);

  /**
   * Returns the appointment last name.
   *
   * @return string
   *   The appointment last name.
   */
  public function getAppointmentLastName();

  /**
   * Sets the appointment last name.
   *
   * @param string $last_name
   *   The appointment last name.
   *
   * @return $this
   */
  public function setAppointmentLastName($last_name);

  /**
   * Returns the appointment phone.
   *
   * @return string
   *   The appointment phone.
   */
  public function getAppointmentPhone();

  /**
   * Sets the appointment phone.
   *
   * @param string $phone
   *   The appointment phone.
   *
   * @return $this
   */
  public function setAppointmentPhone($phone);

  /**
   * Returns the appointment email.
   *
   * @return string
   *   The appointment email.
   */
  public function getAppointmentEmail();

  /**
   * Sets the appointment email.
   *
   * @param string $email
   *   The appointment email.
   *
   * @return $this
   */
  public function setAppointmentEmail($email);

  /**
   * Returns the appointment day.
   *
   * @return \DateTime
   *   The appointment day.
   */
  public function getAppointmentDay();

  /**
   * Sets the appointment day.
   *
   * @param \DateTime $date
   *   The appointment day.
   *
   * @return $this
   */
  public function setAppointmentDay(\DateTime $date);

  /**
   * Returns the appointment hour.
   *
   * @return string
   *   The appointment hour.
   */
  public function getAppointmentHour();

  /**
   * Sets the appointment hour.
   *
   * @param string $hour
   *   The appointment hour.
   *
   * @return $this
   */
  public function setAppointmentHour($hour);

}
