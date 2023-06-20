<?php

namespace Drupal\vactory_decoupled_revalidator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_decoupled_revalidator\RevalidatorEntityTypeInterface;
use Drupal\vactory_decoupled_revalidator\RevalidatorPluginCollection;

/**
 * Defines the revalidator entity type entity type.
 *
 * @ConfigEntityType(
 *   id = "revalidator_entity_type",
 *   label = @Translation("Revalidator entity type"),
 *   label_collection = @Translation("Revalidator entity types"),
 *   label_singular = @Translation("revalidator entity type"),
 *   label_plural = @Translation("revalidator entity types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count revalidator entity type",
 *     plural = "@count revalidator entity types",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\vactory_decoupled_revalidator\RevalidatorEntityTypeListBuilder",
 *     "form" = {
 *       "add" =
 *   "Drupal\vactory_decoupled_revalidator\Form\RevalidatorEntityTypeForm",
 *       "edit" =
 *   "Drupal\vactory_decoupled_revalidator\Form\RevalidatorEntityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "revalidator_entity_type",
 *   admin_permission = "administer revalidator_entity_type",
 *   links = {
 *     "collection" = "/admin/structure/revalidator-entity-type",
 *     "add-form" = "/admin/structure/revalidator-entity-type/add",
 *     "edit-form" =
 *   "/admin/structure/revalidator-entity-type/{revalidator_entity_type}",
 *     "delete-form" =
 *   "/admin/structure/revalidator-entity-type/{revalidator_entity_type}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "revalidator",
 *     "revalidator_configuration"
 *   }
 * )
 */
class RevalidatorEntityType extends ConfigEntityBase implements RevalidatorEntityTypeInterface {

  /**
   * The revalidator entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The revalidator entity type label.
   *
   * @var string
   */
  protected $revalidator;

  /**
   * The configuration of the revalidator plugin.
   *
   * @var array
   */
  protected $revalidator_configuration = [];

  /**
   * The plugin collection that stores revalidator plugins.
   *
   */
  protected $revalidatorPluginCollection;


  public function getRevalidator() {
    return $this->revalidator ? $this->getRevalidatorPluginCollection()
      ->get($this->revalidator) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevalidator(string $plugin_id) {
    $this->revalidator = $plugin_id;
    $this->getRevalidatorPluginCollection()->addInstanceID($plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevalidatorConfiguration() {
    return $this->getRevalidatorPluginCollection()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevalidatorConfiguration(string $id, array $configuration) {
    $collection = $this->getRevalidatorPluginCollection();
    if (!$collection->has($id)) {
      $configuration['id'] = $id;
      $collection->addInstanceId($id, $configuration);
    }
    else {
      $collection->setConfiguration($configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevalidatorPluginCollection() {
    if (!$this->revalidatorPluginCollection) {
      $this->revalidatorPluginCollection = new RevalidatorPluginCollection($this->revalidatorPluginManager(), $this->revalidator, $this->revalidator_configuration, $this->id());
    }
    return $this->revalidatorPluginCollection;
  }

  /**
   * Revalidator plugin manager.
   */
  protected function revalidatorPluginManager() {
    return \Drupal::service('plugin.manager.revalidator');
  }

}
