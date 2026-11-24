<?php

namespace Drupal\vactory_sondage\Plugin\Reminder;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\vactory_reminder\ReminderInterface;
use Drupal\vactory_reminder\SuspendCurrentItemException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a reminder implementation for closing sondage.
 *
 * @Reminder(
 *   id = "sondage_closer",
 *   title = "Sondage closer",
 * )
 */
class SondageCloser extends PluginBase implements ReminderInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $time;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['extra']['entity_type']) || !isset($data['extra']['entity_id']) || !isset($data['extra']['date_field_name'])) {
      throw new SuspendCurrentItemException('Sondage entity infos or date field name are incorrect or missing.');
    }
    $entity = $this->entityTypeManager->getStorage($data['extra']['entity_type'])
      ->load($data['extra']['entity_id']);
    if (!$entity) {
      throw new SuspendCurrentItemException('The given sondage entity is not found.');
    }
    // The reminder queue reads the stored date in the timezone of the site,
    // the field stores it in UTC: on a site ahead of UTC, the item comes
    // before the close date.
    $close_date = $entity->get($data['extra']['date_field_name'])->value;
    $timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    if ($close_date && (new \DateTime($close_date, $timezone))->getTimestamp() > $this->time->getCurrentTime()) {
      throw new SuspendCurrentItemException('The close date of the sondage has not come yet.');
    }
    // Close the concerned sondage.
    $entity->set('field_sondage_status', 0);
    $entity->save();
  }

}
