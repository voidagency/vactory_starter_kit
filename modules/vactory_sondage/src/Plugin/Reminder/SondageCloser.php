<?php

namespace Drupal\vactory_sondage\Plugin\Reminder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
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
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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
    // Close the concerned sondage.
    $entity->set('field_sondage_status', 0);
    $entity->save();
  }

}
