<?php

namespace Drupal\vactory_calendar\Service;

use Drupal\vactory_calendar\Entity\CalendarSlot;

/**
 * Table ReservationInterface.
 */
interface TableReservationInterface {

  public const SEND_INVITATION = 'invitation_mail';

  public const SEND_CONFIRMATION = 'confirmation_mail';

  public const SEND_ANNULATION = 'annulation_mail';

  public const TABLE_RESERVED = 'table_reserved';

  /**
   * {@inheritDoc}
   */
  public function assignTable(CalendarSlot $event);

  /**
   * {@inheritDoc}
   */
  public function freeTable(int $id, $slot = NULL);

  /**
   * {@inheritDoc}
   */
  public function countAvailable();

  /**
   * {@inheritDoc}
   */
  public function notify(string $type, CalendarSlot $event = NULL);

}
